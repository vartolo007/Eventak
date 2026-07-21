<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NewServiceRequestNotification;
use App\Notifications\VendorApprovedServiceNotification; // 👈 استدعاء إشعار القبول
use App\Notifications\VendorRejectedServiceNotification; // 👈 استدعاء إشعار الرفض
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorOrderController extends Controller
{

    // إنشاء خدمة جديدة (تذهب للأدمن كمراجعة أولاً)
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'vendor') {
            return response()->json([
                'status' => 'error',
                'message' => 'فقط الموردين يمكنهم إضافة خدمات'
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('services', 'public');
                $images[] = $path;
            }
        }

        // 🔥 التعديل: الحالة الافتراضية أصبحت pending وليست active
        $service = Service::create([
            'vendor_id' => $user->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'images' => !empty($images) ? $images : null,
            'status' => 'pending'
        ]);

        // 🔔 إشعار الأدمن بوجود طلب خدمة جديد بحاجة لمراجعة
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new NewServiceRequestNotification($service, 'create'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب إضافة الخدمة للأدمن بنجاح وهو قيد المراجعة الآن.',
            'data' => $service
        ], 201);
    }

    // تعديل خدمة (تتحول لـ pending حتى يوافق الأدمن على التعديلات)
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $service = Service::findOrFail($id);

        if ($service->vendor_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية تعديل هذه الخدمة'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // في حال رفع صور جديدة: نحذف الصور القديمة من التخزين ونستبدلها بالجديدة
        if ($request->hasFile('images')) {
            foreach ($service->images ?? [] as $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('services', 'public');
            }
            $validated['images'] = $images;
        }

        // 🔥 التعديل: نحدث البيانات ونعيد حالة الخدمة إلى pending لتختفي لحين المراجعة
        $validated['status'] = 'pending';
        $service->update($validated);

        // 🔔 إشعار الأدمن بوجود تعديل جديد للخدمة
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new NewServiceRequestNotification($service, 'update'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث البيانات وإرسال طلب التعديل للأدمن للمراجعة.',
            'data' => $service
        ]);
    }

    // طلب حذف خدمة (تتحول لـ pending_delete بانتظار موافقة الأدمن)
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $service = Service::findOrFail($id);

        if ($service->vendor_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية حذف هذه الخدمة'
            ], 403);
        }

        // 🔥 التعديل: لا نحذف مباشرة، نغير الحالة إلى pending_delete لتظهر عند الأدمن
        $service->update(['status' => 'pending_delete']);

        // 🔔 إشعار الأدمن بطلب الحذف
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new NewServiceRequestNotification($service, 'delete'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تقديم طلب حذف الخدمة للأدمن بنجاح، ستتم إزالتها فور الموافقة.'
        ]);
    }


    /**
     * 1. جلب كافة المناسبات التي تطلب خدمات تابعة للمورد الحالي
     */
    public function index(Request $request)
    {
        $user = $request->user(); // المورد الحالي

        // جلب المناسبات التي تحتوي على خدمات تابعة لهذا المورد وحالتها العامة بانتظار الموردين
        // مع جلب تفاصيل الخدمات المطلوبة منه فقط في تلك المناسبة
        $events = Event::where('status', 'vendor_pending')
            ->whereHas('services', function ($query) use ($user) {
                $query->where('vendor_id', $user->id); // الفلترة بـ id المورد داخل جدول الخدمات
            })
            ->with(['services' => function ($query) use ($user) {
                $query->where('vendor_id', $user->id); // جلب خدماته هو فقط في الـ Response
            }, 'venue', 'customer:id,name,phone'])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $events->count(),
            'data' => $events
        ], 200);
    }

    /**
     * 2. قبول المورد لخدمة معينة داخل مناسبة
     */
    public function acceptService(Request $request, $eventId, $serviceId)
    {
        $user = $request->user();
        $event = Event::findOrFail($eventId);
        $service = Service::findOrFail($serviceId); // جلب الخدمة لتمريرها للإشعار

        if ($event->status !== 'vendor_pending') {
            return response()->json(['status' => 'error', 'message' => 'هذه المناسبة ليست في مرحلة مراجعة الموردين.'], 422);
        }

        $servicePivot = $event->services()->where('service_id', $serviceId)->where('vendor_id', $user->id)->first();
        if (!$servicePivot) {
            return response()->json(['status' => 'error', 'message' => 'هذه الخدمة غير موجودة أو غير تابعة لك.'], 403);
        }

        // تحديث السجل في الجدول الوسيط
        $event->services()->updateExistingPivot($serviceId, ['status' => 'accepted']);

        // 🔔 إرسال إشعار للزبون: وافق المورد على هذه الخدمة المحددة
        if ($event->customer) {
            $event->customer->notify(new VendorApprovedServiceNotification($event, $service));
        }

        // خوارزمية فحص اكتمال موافقة بقية الموردين (لتصعيد المناسبة بالكامل إذا نجحت)
        $this->checkAndUpgradeEventStatus($event);

        return response()->json(['status' => 'success', 'message' => 'تم قبول تلبية الخدمة بنجاح، وإشعار الزبون.'], 200);
    }

    /**
     * 3. رفض المورد لخدمة معينة داخل مناسبة
     */
    public function rejectService(Request $request, $eventId, $serviceId)
    {
        $user = $request->user();
        $event = Event::findOrFail($eventId);
        $service = Service::findOrFail($serviceId);

        if ($event->status !== 'vendor_pending') {
            return response()->json(['status' => 'error', 'message' => 'لا يمكن اتخاذ إجراء حالياً.'], 422);
        }

        $servicePivot = $event->services()->where('service_id', $serviceId)->where('vendor_id', $user->id)->first();
        if (!$servicePivot) {
            return response()->json(['status' => 'error', 'message' => 'هذه الخدمة غير موجودة أو غير تابعة لك.'], 403);
        }

        // تحديث السجل في الجدول الوسيط إلى مرفوض
        $event->services()->updateExistingPivot($serviceId, ['status' => 'rejected']);

        // إلغاء المناسبة تلقائياً لأن غياب أحد الخدمات الأساسية يعطل الحجز الكامل
        $event->update([
            'status' => 'cancelled',
            'rejection_reason' => "تم رفض الطلب واعتذار المورد عن تقديم خدمة ({$service->name})."
        ]);

        // 🔔 إرسال إشعار للزبون: المورد اعتذر والطلب أُلغي
        if ($event->customer) {
            $event->customer->notify(new VendorRejectedServiceNotification($event, $service));
        }

        return response()->json(['status' => 'success', 'message' => 'تم اعتذار المورد عن الخدمة، وإلغاء المناسبة وإشعار الزبون.'], 200);
    }

    /**
     * جلب واستعراض كافة الخدمات والباقات الخاصة بالمورد الحالي
     */
    public function myServices(Request $request)
    {
        $user = $request->user();

        $services = Service::where('vendor_id', $user->id)
            ->with('category:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $services->count(),
            'data' => $services
        ], 200);
    }

    /**
     * دالة مساعدة ذكية تفحص إذا وافق جميع الموردين في المناسبة لتحويل حالتها لـ confirmed جاهزة للدفع
     */
    private function checkAndUpgradeEventStatus($event)
    {
        // جلب حالات جميع الخدمات المرتبطة بهذه المناسبة في الجدول الوسيط
        $allServicesStatus = DB::table('event_services')
            ->where('event_id', $event->id)
            ->pluck('status')
            ->toArray();

        // إذا كانت كل الخدمات الملحقة بالمناسبة أصبحت حالتها 'confirmed'
        if (!in_array('pending', $allServicesStatus) && !in_array('rejected', $allServicesStatus)) {
            // تصعيد حالة المناسبة بالكامل لتصبح مؤكدة وبانتظار دفع الزبون الفاتورة
            $event->update(['status' => 'confirmed']);

            // إشعار الزبون فوراً عبر نظام الإشعارات بأن حجزه جاهز بالكامل للدفع والاعتماد!
            if ($event->customer) {
                $event->customer->notify(new \App\Notifications\EventConfirmedNotification($event));
            }
        }
    }
}
