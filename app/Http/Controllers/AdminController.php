<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use App\Models\ServiceCategory; // 👈 أضف هذا الاستيراد
use App\Models\Venue;
use App\Models\VenueRequest;
use App\Notifications\ServiceRequestResultNotification;
use App\Notifications\VenueRequestResultNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function addUser(Request $request)
    {
        $validatedData = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'role'  => 'required|in:vendor,venue_owner', // تحديد نوع الحساب
        ]);

        $user = User::create($validatedData);

        return response()->json([
            'message' => __('messages.admin.user_added_success'),
            'user'    => $user,
        ]);
    }

    // 1. عرض كافة طلبات الخدمات المعلقة (إضافة أو تعديل أو حذف)
    public function serviceRequests()
    {
        $requests = Service::whereIn('status', ['pending', 'pending_delete'])->with('vendor')->get();
        return response()->json(['status' => 'success', 'data' => $requests]);
    }

    /**
     * 1. دالة الموافقة على طلبات الخدمات (تفعيل الإضافة/التعديل أو تأكيد الحذف)
     */
    public function approveService($id)
    {
        $service = Service::findOrFail($id);

        // إذا كان المورد يطلب حذف الخدمة، ووافق الأدمن، نقوم بحذفها نهائياً
        if ($service->status === 'pending_delete') {
            // 🔔 إشعار المورد قبل الحذف لضمان توفر بياناته
            if ($service->vendor) {
                $service->vendor->notify(new ServiceRequestResultNotification($service, 'delete_approved'));
            }

            $service->delete();

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.admin.service_delete_approved'),
            ], 200);
        }

        // إذا كان الطلب إضافة جديدة أو تعديل، نقوم بتحويل الحالة إلى active لتظهر للزبائن
        if ($service->status === 'pending') {
            $service->update(['status' => 'active']);

            // 🔔 إشعار المورد بأن خدمته أصبحت حية ونشطة
            if ($service->vendor) {
                $service->vendor->notify(new ServiceRequestResultNotification($service, 'approved'));
            }

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.admin.service_approved'),
                'data'    => $service,
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => __('messages.admin.service_no_action_required'),
        ], 400);
    }

    /**
     * 2. دالة الرفض لطلبات الخدمات (رفض الإضافة أو رفض الحذف)
     */
    public function rejectService($id)
    {
        $service = Service::findOrFail($id);

        // إذا رفض الأدمن طلب الحذف، نعيد الخدمة نشطة (active) كما كانت
        if ($service->status === 'pending_delete') {
            $service->update(['status' => 'active']);

            // 🔔 إشعار المورد برفض طلب الحذف
            if ($service->vendor) {
                $service->vendor->notify(new ServiceRequestResultNotification($service, 'delete_rejected'));
            }

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.admin.service_delete_rejected'),
                'data'    => $service,
            ], 200);
        }

        // إذا رفض الأدمن الإضافة الجديدة أو التعديل، نحولها إلى inactive (غير نشطة) لكي لا يراها الزبائن ويعدلها المورد لاحقاً
        if ($service->status === 'pending') {
            $service->update(['status' => 'inactive']);

            // 🔔 إشعار المورد برفض إضافة الخدمة ليتأكد من بياناتها
            if ($service->vendor) {
                $service->vendor->notify(new ServiceRequestResultNotification($service, 'rejected'));
            }

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.admin.service_rejected'),
                'data'    => $service,
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => __('messages.admin.service_reject_not_pending'),
        ], 400);
    }

    /**
     * عرض كافة طلبات الصالات المعلقة للأدمن (طلبات الإنشاء، التعديل، والحذف)
     */
    public function venueRequests()
    {
        // جلب كافة السجلات التي حالتها pending (معلقة) مع بيانات صاحب الصالة (User)
        $requests = VenueRequest::where('status', 'pending')
            ->with('owner')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count'  => $requests->count(),
            'data'   => $requests,
        ], 200);
    }

    public function approveVenueRequest($requestId)
    {
        $venueRequest = VenueRequest::findOrFail($requestId);

        if ($venueRequest->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => __('messages.admin.venue_request_already_processed')], 400);
        }

        // 1. موافقة على إضافة صالة جديدة كلياً
        if ($venueRequest->type === 'create') {
            $venue = new Venue();
            $venue->owner_id = $venueRequest->owner_id;
            $venue->capacity = $venueRequest->capacity;
            $venue->price = $venueRequest->price;
            $venue->cover_image = $venueRequest->cover_image;
            $venue->images = $venueRequest->images;
            $venue->status = 'active';
            foreach (['name', 'address', 'description'] as $field) {
                $venue->setTranslations($field, $venueRequest->getTranslations($field));
            }
            $venue->save();
        }

        // 2. موافقة على تعديل صالة قائمة
        if ($venueRequest->type === 'update') {
            $venue = Venue::findOrFail($venueRequest->venue_id);
            $oldCoverImage = $venue->cover_image;
            $oldImages = $venue->images ?? [];

            $venue->capacity = $venueRequest->capacity;
            $venue->price = $venueRequest->price;
            $venue->cover_image = $venueRequest->cover_image;
            $venue->images = $venueRequest->images;
            foreach (['name', 'address', 'description'] as $field) {
                $venue->setTranslations($field, $venueRequest->getTranslations($field));
            }
            $venue->save();

            if ($oldCoverImage && $oldCoverImage !== $venueRequest->cover_image) {
                Storage::disk('public')->delete($oldCoverImage);
            }

            $newImages = $venueRequest->images ?? [];
            foreach ($oldImages as $oldImage) {
                if (! in_array($oldImage, $newImages, true)) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
        }

        // 3. موافقة على حذف صالة
        if ($venueRequest->type === 'delete') {
            $venue = Venue::findOrFail($venueRequest->venue_id);
            $venue->delete();
        }

        // تحديث حالة الطلب نفسه في جدول الطلبات (حيث يوجد حقل status كـ enum فعلياً)
        $venueRequest->update(['status' => 'approved']);

        // 🔔 إشعار صاحب الصالة بالموافقة على طلبه
        $owner = $venueRequest->owner;
        if ($owner) {
            $owner->notify(new VenueRequestResultNotification($venueRequest, 'approved'));
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.admin.venue_request_approved'),
        ], 200);
    }

    /**
     * دالة رفض طلبات الصالات (سواء رفض إضافة، رفض تعديل، أو رفض حذف)
     */
    public function rejectVenueRequest(Request $request, $requestId)
    {
        // 1. جلب سطر الطلب من جدول الطلبات المؤقت
        $venueRequest = VenueRequest::findOrFail($requestId);

        // 2. التأكد من أن الطلب ما زال معلقاً ولم يتم البت فيه سابقاً
        if ($venueRequest->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.admin.venue_request_already_processed'),
            ], 400);
        }

        // 3. التحقق من استقبال سبب الرفض إجبارياً بحسب الـ UI Flow
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        // 4. تحديث حالة الطلب وحفظ سبب الرفض في جدول الـ requests
        $venueRequest->update([
            'status'      => 'rejected',
            'admin_notes' => $validated['admin_notes'],
        ]);

        // 💡 المنطق البرمجي للرفض بحسب نوع الطلب:
        switch ($venueRequest->type) {
            case 'create':
                $this->deleteVenueRequestFiles($venueRequest);
                $message = __('messages.admin.venue_request_rejected_create');
                break;

            case 'update':
                $this->deleteVenueRequestFiles($venueRequest);
                $message = __('messages.admin.venue_request_rejected_update');
                break;

            case 'delete':
                $message = __('messages.admin.venue_request_rejected_delete');
                break;

            default:
                $message = __('messages.admin.venue_request_rejected');
        }

        // 🔔 إشعار صاحب الصالة برفض طلبه مع السبب
        $owner = $venueRequest->owner;
        if ($owner) {
            $owner->notify(new VenueRequestResultNotification($venueRequest, 'rejected'));
        }

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $venueRequest,
        ], 200);
    }

    private function deleteVenueRequestFiles(VenueRequest $venueRequest): void
    {
        if ($venueRequest->cover_image) {
            Storage::disk('public')->delete($venueRequest->cover_image);
        }

        foreach ($venueRequest->images ?? [] as $image) {
            Storage::disk('public')->delete($image);
        }
    }

    /**
     * 1. جلب وعرض قائمة كافة المستخدمين والملاك والموردين بالنظام
     * (Users Management)
     */
    public function getAllUsers()
    {
        $users = User::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'count'  => $users->count(),
            'data'   => $users,
        ], 200);
    }

    /**
     * 2. جلب كل الصالات الفعالة والمقبولة بالسيستم
     * (Active Venues)
     */
    public function getActiveVenues()
    {
        $venues = Venue::where('status', 'active')
            ->with('owner:id,name,email,phone')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count'  => $venues->count(),
            'data'   => $venues,
        ], 200);
    }

    /**
     * 3. تصفح ومراقبة كل الحفلات والمناسبات القائمة وحالاتها المالية
     * (All Events & Monitoring)
     */
    public function getAllEvents()
    {
        // جلب كافة المناسبات مع تفاصيل الزبون، الصالة، الخدمات (بأسعارها وكمياتها وقت الحجز)، والفاتورة
        $events = Event::with([
            'customer:id,name,phone',
            'venue:id,name,price',
            'services' => function ($query) {
                // نجلب أعمدة جدول services الأساسية + بيانات الجدول الوسيط (السعر والكمية والحالة وقت الحجز)
                $query->select('services.id', 'services.name')
                    ->withPivot('quantity', 'price', 'status', 'rejection_reason');
            },
            'invoice',
        ])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'count'  => $events->count(),
            'data'   => $events,
        ], 200);
    }

    /**
     * 4. عرض جميع التقييمات في التطبيق (تقييمات المناسبات/الصالات)
     */
    public function getAllRatings()
    {
        $ratings = Rating::with([
            'customer:id,name,phone,email',
            'event:id,venue_id,event_name,event_type,date,status',
            'event.venue:id,name,address',
        ])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'count'  => $ratings->count(),
            'data'   => $ratings,
        ], 200);
    }

    /**
     * 5. عرض جميع المدفوعات والملخص المالي الكامل للإدارة
     */
    public function getAllPaymentsAndFinance()
    {
        $payments = Payment::with([
            'invoice:id,event_id,total_amount,status,venue_price,services_total',
            'invoice.event:id,customer_id,venue_id,event_name,date,status',
            'invoice.event.customer:id,name,phone,email',
            'invoice.event.venue:id,name,address',
        ])->orderBy('created_at', 'desc')->get();

        $financeSummary = [
            'payments_count' => $payments->count(),
            'successful_payments_count' => Payment::where('status', 'success')->count(),
            'failed_payments_count' => Payment::where('status', 'failed')->count(),
            'refunded_payments_count' => Payment::where('status', 'refunded')->count(),
            'total_collected_amount' => (float) Payment::where('status', 'success')->sum('amount'),
            'total_refunded_amount' => (float) Payment::sum('refund_amount'),
            'paid_invoices_count' => Invoice::where('status', 'paid')->count(),
            'pending_invoices_count' => Invoice::where('status', 'pending')->count(),
            'total_invoices_amount' => (float) Invoice::sum('total_amount'),
        ];

        return response()->json([
            'status' => 'success',
            'summary' => $financeSummary,
            'count' => $payments->count(),
            'data' => $payments,
        ], 200);
    }

    /**
     * عرض جميع تصنيفات الخدمات.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexServiceCategories()
    {
        $categories = ServiceCategory::all();
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * إضافة تصنيف خدمة جديد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeServiceCategory(Request $request)
    {
        $locale = app()->getLocale();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $exists = ServiceCategory::where("name->{$locale}", $validatedData['name'])->exists();
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => __('validation.unique', ['attribute' => 'name']),
            ], 422);
        }

        $category = ServiceCategory::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.admin.category_created'),
            'category' => $category
        ], 201);
    }

    /**
     * تحديث تصنيف خدمة موجود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateServiceCategory(Request $request, $id)
    {
        $category = ServiceCategory::findOrFail($id);
        $locale = app()->getLocale();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $exists = ServiceCategory::where("name->{$locale}", $validatedData['name'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => __('validation.unique', ['attribute' => 'name']),
            ], 422);
        }

        $category->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.admin.category_updated'),
            'category' => $category
        ]);
    }

    /**
     * حذف تصنيف خدمة.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyServiceCategory($id)
    {
        $category = ServiceCategory::findOrFail($id);

        // تحقق اختياري: إذا كانت هناك خدمات مرتبطة بهذا التصنيف، امنع الحذف
        if ($category->services()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.admin.category_delete_blocked')
            ], 409); // Conflict
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('messages.admin.category_deleted')
        ]);
    }
}
