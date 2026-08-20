<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Service;
use App\Models\Venue;
use App\Notifications\EventApprovedNotification;
use App\Notifications\EventConfirmedNotification;
use App\Notifications\EventRejectedNotification;
use Carbon\Carbon;

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
                'status' => 'success',
                'count'  => 0,
                'data'   => [],
                'message' => __('messages.venue.no_venues')
            ], 200);
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
        $event = Event::with('venue', 'customer')->findOrFail($id); // Eager load customer for notification

        // التأكد أن المستخدم الحالي هو فعلاً صاحب الصالة المحجوزة
        if (!$event->venue || $event->venue->owner_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.venue.unauthorized')
            ], 403);
        }

        // التأكد أن حالة الحجز الحالية هي pending فقط
        if ($event->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.venue.not_pending_accept')
            ], 422);
        }

        // تحديث الحالة بحسب الـ Workflow المطلوبة:
        // إذا كانت المناسبة تحتوي خدمات ننتظر موافقة الموردين، وإلا تصبح مؤكدة مباشرة وجاهزة للدفع
        $hasServices = $event->services()->exists();
        $event->status = $hasServices ? 'vendor_pending' : 'confirmed';
        $event->save();

        if ($event->customer) {
            // إشعار القبول عند انتظار الموردين، أو إشعار التأكيد الجاهز للدفع إن لم توجد خدمات
            $event->customer->notify($hasServices
                ? new EventApprovedNotification($event)
                : new EventConfirmedNotification($event));
        }

        // --- Start: Logic to handle conflicting events ---
        $acceptedEventStart = Carbon::parse($event->date . ' ' . $event->start_time);
        $acceptedEventEnd = Carbon::parse($event->date . ' ' . $event->end_time);
        // إضافة ساعة التنظيف الإلزامية للحجز المقبول
        $acceptedEventEndWithCleaning = (clone $acceptedEventEnd)->addHour();

        // البحث عن الحجوزات المتضاربة لنفس الصالة ونفس التاريخ
        $conflictingEvents = Event::where('venue_id', $event->venue_id)
            ->where('date', $event->date)
            ->where('id', '!=', $event->id) // استبعاد الحجز الذي تم قبوله للتو
            ->whereIn('status', ['pending', 'venue_pending']) // فقط الحجوزات التي تنتظر موافقة صاحب الصالة أو في حالة pending الأولية
            ->with('customer') // تحميل بيانات الزبون لإرسال الإشعار
            ->get();

        foreach ($conflictingEvents as $conflictingEvent) {
            $conflictingEventStart = Carbon::parse($conflictingEvent->date . ' ' . $conflictingEvent->start_time);
            $conflictingEventEnd = Carbon::parse($conflictingEvent->date . ' ' . $conflictingEvent->end_time);

            // التحقق من التداخل الزمني مع الحجز المقبول (بما في ذلك ساعة التنظيف)
            if ($conflictingEventStart->lt($acceptedEventEndWithCleaning) && $conflictingEventEnd->gt($acceptedEventStart)) {
                // تم العثور على تضارب!
                // تحديث حالة الحجز المتضارب إلى ملغى وتحديد سبب الإلغاء
                $conflictingEvent->update([
                    'status' => 'cancelled',
                    'rejection_reason' => 'تم إلغاء طلب حجز الصالة بسبب قبول طلب حجز آخر (ID: ' . $event->id . ') لنفس الصالة والوقت. يرجى اختيار صالة أو وقت آخر.'
                ]);

                // إشعار الزبون صاحب الحجز الملغى
                if ($conflictingEvent->customer) {
                    $conflictingEvent->customer->notify(new EventRejectedNotification(
                        $conflictingEvent,
                        'rejected',
                        'تم إلغاء طلب حجز الصالة بسبب قبول طلب حجز آخر (ID: ' . $event->id . ') لنفس الصالة والوقت. يرجى اختيار صالة أو وقت آخر.'
                    ));
                }
            }
        }
        // --- End: Logic to handle conflicting events ---

        return response()->json([
            'status' => 'success',
            'message' => $hasServices
                ? __('messages.venue.accept_with_services')
                : __('messages.venue.accept_success'),
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
                'message' => __('messages.venue.unauthorized')
            ], 403);
        }

        // منع رفض الطلبات المدفوعة أو المنتهية أو الملغاة مسبقاً
        // (الحجز المدفوع لا يمكن رفضه لعدم وجود آلية استرجاع مبلغ حالياً)
        if (in_array($event->status, ['paid', 'cancelled', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.venue.reject_not_allowed')
            ], 422);
        }

        // تحديث البيانات وحالة الرفض
        $event->status = 'cancelled';
        $event->rejection_reason = $request->rejection_reason;
        $event->save();

        $customer = $event->customer;
        if ($customer) {
            $customer->notify(new EventRejectedNotification(
                $event,
                'rejected',
                $request->rejection_reason // استخدام سبب الرفض الذي أدخله صاحب الصالة
            ));
        }

        return response()->json([
            'status' => 'success',
            'message' => __('messages.venue.reject_success'),
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
                'message' => __('messages.venue.unauthorized')
            ], 403);
        }

        if ($event->status !== 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.venue.complete_not_paid')
            ], 422);
        }

        $event->update(['status' => 'completed']);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.venue.complete_success'),
            'data' => $event
        ], 200);
    }
}
