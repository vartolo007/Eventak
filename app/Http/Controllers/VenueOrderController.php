<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Service;
use App\Models\Venue;
use App\Notifications\EventApprovedNotification;
use App\Notifications\EventConfirmedNotification;
use App\Notifications\EventRejectedNotification;

class VenueOrderController extends Controller
{
    /**
     * 1. استقبال وجلب طلبات الحجز الخاصة بصالة المستخدم الحالي (لصاحب الصالة)
     */
    public function ownerIndex(Request $request)
    {
        $user = $request->user();

        // جلب كل الصالات التي يملكها هذا المستخدم (قد يملك أكثر من صالة)
        $venueIds = Venue::where('owner_id', $user->id)->pluck('id');

        if ($venueIds->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'لم يتم العثور على صالة مسجلة باسمك بعد.'
            ], 404);
        }

        // جلب جميع المناسبات المرتبطة بكل صالاته مع بيانات الزبون الذي حجزها
        $events = Event::whereIn('venue_id', $venueIds)
            ->with(['customer:id,name,email,phone', 'venue:id,name']) // جلب بيانات الزبون واسم الصالة
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $events
        ], 200);
    }

    /**
     * 2. قبول طلب الحجز وتصعيد الحالة (من قبل صاحب الصالة)
     */
    public function accept($id, Request $request)
    {
        $user = $request->user();

        // الإصلاح الثاني: تحميل علاقة الـ venue مع المناسبة لضمان قراءة المالك بأمان
        $event = Event::with('venue')->findOrFail($id);

        // التأكد أن المستخدم الحالي هو فعلاً صاحب الصالة المحجوزة
        if (!$event->venue || $event->venue->owner_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح لك باتخاذ قرار بشأن هذا الحجز.'
            ], 403);
        }

        // التأكد أن حالة الحجز الحالية هي pending فقط
        if ($event->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن قبول هذا الطلب لأنه ليس في حالة قيد الانتظار القابلة للقبول.'
            ], 422);
        }

        // تحديث الحالة بحسب الـ Workflow المطلوبة:
        // إذا كانت المناسبة تحتوي خدمات ننتظر موافقة الموردين، وإلا تصبح مؤكدة مباشرة وجاهزة للدفع
        $hasServices = $event->services()->exists();
        $event->status = $hasServices ? 'vendor_pending' : 'confirmed';
        $event->save();

        $customer = $event->customer; // تأكد أن علاقة customer معرفة في موديل Event
        if ($customer) {
            // إشعار القبول عند انتظار الموردين، أو إشعار التأكيد الجاهز للدفع إن لم توجد خدمات
            $customer->notify($hasServices
                ? new EventApprovedNotification($event)
                : new EventConfirmedNotification($event));
        }

        return response()->json([
            'status' => 'success',
            'message' => $hasServices
                ? 'تم قبول طلب الحجز بنجاح، وتم تحويل الحالة إلى بانتظار الموردين (vendor_pending).'
                : 'تم قبول طلب الحجز بنجاح، ولعدم وجود خدمات مطلوبة أصبح الحجز مؤكداً (confirmed) وجاهزاً للدفع مباشرة.',
            'data' => $event
        ], 200);
    }

    /**
     * 3. رفض طلب الحجز مع تدوين سبب الرفض الإلزامي
     */
    public function reject(Request $request, $id)
    {
        // التحقق من إلزامية تدوين سبب الرفض
        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:1000',
        ]);

        $user = $request->user();

        // الإصلاح الثالث: تحميل علاقة الـ venue مع المناسبة هنا أيضاً للحماية
        $event = Event::with('venue')->findOrFail($id);

        // التأكد من الملكية
        if (!$event->venue || $event->venue->owner_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح لك باتخاذ قرار بشأن هذا الحجز.'
            ], 403);
        }

        // منع رفض الطلبات المدفوعة أو المنتهية أو الملغاة مسبقاً
        // (الحجز المدفوع لا يمكن رفضه لعدم وجود آلية استرجاع مبلغ حالياً)
        if (in_array($event->status, ['paid', 'cancelled', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن رفض هذا الطلب لأنه مدفوع أو ملغى أو مكتمل بالفعل.'
            ], 422);
        }

        // تحديث البيانات وحالة الرفض
        $event->status = 'cancelled';
        $event->rejection_reason = $request->rejection_reason;
        $event->save();

        $customer = $event->customer;
        if ($customer) {
            $customer->notify(new EventRejectedNotification($event));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفض طلب الحجز بنجاح وتدوين السبب للزبون.',
            'data' => $event
        ], 200);
    }

    /**
     * 4. تعليم المناسبة كمكتملة بعد انتهائها فعلياً (من قبل صاحب الصالة)
     */
    public function complete(Request $request, $id)
    {
        $user = $request->user();
        $event = Event::with('venue')->findOrFail($id);

        if (!$event->venue || $event->venue->owner_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح لك باتخاذ قرار بشأن هذا الحجز.'
            ], 403);
        }

        if ($event->status !== 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن إغلاق المناسبة إلا بعد اكتمال الدفع.'
            ], 422);
        }

        $event->update(['status' => 'completed']);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تعليم المناسبة كمكتملة بنجاح.',
            'data' => $event
        ], 200);
    }
}
