<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use App\Models\Event;
use App\Models\VenueRequest;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function addUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'role' => 'required|in:vendor,venue_owner' // تحديد نوع الحساب
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            // لا يوجد حقل كلمة مرور هنا، لأن المورد سيدخل عبر الـ OTP حصراً!
        ]);

        return response()->json([
            'message' => 'تم إضافة المورد بنجاح، يمكنه الآن الدخول عبر الـ OTP',
            'user' => $user
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
            $service->delete();

            // هنا يمكنك إرسال إشعار للمورد بأنه تم قبول طلب الحذف
            // $service->vendor->notify(new ServiceActionNotification($service, 'deleted'));

            return response()->json([
                'status' => 'success',
                'message' => 'تمت الموافقة على طلب الحذف، وتمت إزالة الخدمة نهائياً من النظام.'
            ], 200);
        }

        // إذا كان الطلب إضافة جديدة أو تعديل، نقوم بتحويل الحالة إلى active لتظهر للزبائن
        if ($service->status === 'pending') {
            $service->update(['status' => 'active']);

            // هنا يمكنك إرسال إشعار للمورد بأن خدمته أصبحت حية ونشطة
            // $service->vendor->notify(new ServiceActionNotification($service, 'approved'));

            return response()->json([
                'status' => 'success',
                'message' => 'تمت الموافقة على الخدمة وتفعيلها بنجاح، وهي الآن معروضة في التطبيق.',
                'data' => $service
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'هذه الخدمة ليست بحاجة إلى موافقة حالياً.'
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

            // إشعار المورد برفض طلب الحذف
            // $service->vendor->notify(new ServiceActionNotification($service, 'delete_rejected'));

            return response()->json([
                'status' => 'success',
                'message' => 'تم رفض طلب الحذف، وأعيدت الخدمة نشطة ومتوفرة في التطبيق.',
                'data' => $service
            ], 200);
        }

        // إذا رفض الأدمن الإضافة الجديدة أو التعديل، نحولها إلى inactive (غير نشطة) لكي لا يراها الزبائن ويعدلها المورد لاحقاً
        if ($service->status === 'pending') {
            $service->update(['status' => 'inactive']);

            // إشعار المورد برفض إضافة الخدمة ليتأكد من بياناتها
            // $service->vendor->notify(new ServiceActionNotification($service, 'rejected'));

            return response()->json([
                'status' => 'success',
                'message' => 'تم رفض طلب الخدمة، وتم تحويل حالتها إلى غير نشطة (inactive).',
                'data' => $service
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'هذه الخدمة ليست في حالة مراجعة لطلب الرفض.'
        ], 400);
    }

    /**
     * عرض كافة طلبات الصالات المعلقة للأدمن (طلبات الإنشاء، التعديل، والحذف)
     */
    public function venueRequests()
    {
        // جلب كافة السجلات التي حالتها pending (معلقة) مع بيانات صاحب الصالة (User)
        $requests = VenueRequest::where('status', 'pending')
            ->with('owner') // افترضنا أن هناك علاقة اسمها owner في موديل VenueRequest تربطه بالمستخدم
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $requests->count(),
            'data' => $requests
        ], 200);
    }

    public function approveVenueRequest($requestId)
    {
        $venueRequest = VenueRequest::findOrFail($requestId);

        if ($venueRequest->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'هذا الطلب معالج مسبقاً'], 400);
        }

        // 1. موافقة على إضافة صالة جديدة كلياً
        if ($venueRequest->type === 'create') {
            Venue::create([
                'owner_id'    => $venueRequest->owner_id, // 👈 تعديل الاسم ليطابق الـ Migration (owner_id)
                'name'        => $venueRequest->name,
                'capacity'    => $venueRequest->capacity,
                'price'       => $venueRequest->price,
                'address'     => $venueRequest->address,
                'description' => $venueRequest->description,
                'cover_image' => $venueRequest->cover_image, // 👈 إضافة الحقول لكي لا تضيع الصور عند النقل
                'images'      => $venueRequest->images,
            ]);
        }

        // 2. موافقة على تعديل صالة قائمة
        if ($venueRequest->type === 'update') {
            $venue = Venue::findOrFail($venueRequest->venue_id);
            $venue->update([
                'name'        => $venueRequest->name,
                'capacity'    => $venueRequest->capacity,
                'price'       => $venueRequest->price,
                'address'     => $venueRequest->address,
                'description' => $venueRequest->description,
                'cover_image' => $venueRequest->cover_image,
                'images'      => $venueRequest->images,
            ]);
        }

        // 3. موافقة على حذف صالة
        if ($venueRequest->type === 'delete') {
            $venue = Venue::findOrFail($venueRequest->venue_id);
            $venue->delete();
        }

        // تحديث حالة الطلب نفسه في جدول الطلبات (حيث يوجد حقل status كـ enum فعلياً)
        $venueRequest->update(['status' => 'approved']);

        return response()->json([
            'status' => 'success',
            'message' => 'تمت معالجة طلب الصالة والموافقة عليه بنجاح وتحديث البيانات الحية.'
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
                'status' => 'error',
                'message' => 'هذا الطلب تم اتخاذ إجراء سابق عليه بالفعل.'
            ], 400);
        }

        // 3. التحقق من استقبال سبب الرفض إجبارياً بحسب الـ UI Flow
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000'
        ]);

        // 4. تحديث حالة الطلب وحفظ سبب الرفض في جدول الـ requests
        $venueRequest->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'] // 👈 حفظ السبب ليراه صاحب الصالة ببروفايله
        ]);

        // 💡 المنطق البرمجي للرفض بحسب نوع الطلب:
        switch ($venueRequest->type) {
            case 'create':
                $message = 'تم رفض طلب إنشاء الصالة بنجاح، ولن تظهر في النظام.';
                break;

            case 'update':
                $message = 'تم رفض طلب التعديل، وبقيت بيانات الصالة الأصلية الحية دون أي تغيير.';
                break;

            case 'delete':
                $message = 'تم رفض طلب الحذف، وظلت الصالة نشطة ومتاحة للحجوزات في التطبيق.';
                break;

            default:
                $message = 'تم رفض الطلب بنجاح.';
        }

        // 🔔 هنا نرسل إشعاراً لصاحب الصالة يخبره برفض طلبه لتعديل بياناته أو مراجعة الإدارة
        // $venueRequest->owner->notify(new VenueRequestRejectedNotification($venueRequest));

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $venueRequest
        ], 200);
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
            'count' => $users->count(),
            'data' => $users
        ], 200);
    }

    /**
     * 2. جلب كل الصالات الفعالة والمقبولة بالسيستم
     * (Active Venues)
     */
    public function getActiveVenues()
    {
        // 💡 تم حذف ->where('status', 'active') لأن وجود السجل بجدول venues يعني أنه فعال تلقائياً
        $venues = Venue::with('owner:id,name,email,phone') // 👈 تأكد من وجود علاقة owner داخل موديل Venue
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $venues->count(),
            'data' => $venues
        ], 200);
    }

    /**
     * 3. تصفح ومراقبة كل الحفلات والمناسبات القائمة وحالاتها المالية
     * (All Events & Monitoring)
     */
    public function getAllEvents()
    {
        // جلب كافة المناسبات مع تفاصيل الزبون، الصالة، الخدمات، والفاتورة المرتبطة بها
        $events = Event::with([
            'customer:id,name,phone',
            'venue:id,name,price',
            'services:id,name,price',
            'invoice'
        ])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'count' => $events->count(),
            'data' => $events
        ], 200);
    }
}
