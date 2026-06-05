<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Venue;

class VenueOrderController extends Controller
{
    /**
     * 1. استقبال وجلب طلبات الحجز الخاصة بصالة المستخدم الحالي (لصاحب الصالة)
     */
    public function ownerIndex(Request $request)
    {
        $user = $request->user();

        // جلب الصالة التي يملكها هذا المستخدم
        $venue = Venue::where('owner_id', $user->id)->first();

        if (!$venue) {
            return response()->json([
                'status' => 'error',
                'message' => 'لم يتم العثور على صالة مسجلة باسمك بعد.'
            ], 404);
        }

        // جلب جميع المناسبات المرتبطة بهذه الصالة مع بيانات الزبون الذي حجزها
        $events = Event::where('venue_id', $venue->id)
            ->with('customer:id,name,email,phone') // جلب بيانات الزبون الأساسية
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

        // تحديث الحالة بحسب الـ Workflow المطلوبة
        $event->status = 'vendor_pending';
        $event->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم قبول طلب الحجز بنجاح، وتم تحويل الحالة إلى بانتظار الموردين (vendor_pending).',
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

        // منع رفض الطلبات المنتهية أو الملغاة مسبقاً
        if (in_array($event->status, ['cancelled', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن رفض هذا الطلب لأنه ملغى أو مكتمل بالفعل.'
            ], 422);
        }

        // تحديث البيانات وحالة الرفض
        $event->status = 'cancelled';
        $event->rejection_reason = $request->rejection_reason;
        $event->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفض طلب الحجز بنجاح وتدوين السبب للزبون.',
            'data' => $event
        ], 200);
    }
}
