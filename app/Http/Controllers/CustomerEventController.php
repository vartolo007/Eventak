<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;

class CustomerEventController extends Controller
{
    /**
     * محرك إنشاء مناسبة جديدة للزبون مع خوارزمية ساعة التنظيف
     */
    public function store(Request $request)
    {
        // 1. التحقق من المدخلات الأساسية القادمة من الزبون
        $validatedData = $request->validate([
            'venue_id' => 'required|exists:venues,id',
            'date' => 'required|date|after_or_equal:today', // منع الحجز في تواريخ قديمة
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time', // وقت الانتهاء بعد البدء
            'guests_count' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        // 2. جلب بيانات الصالة للتحقق من السعر والاستيعاب
        $venue = Venue::findOrFail($request->venue_id);

        // 3. فحص القدرة الاستيعابية للصالة
        if ($request->guests_count > $venue->capacity) {
            return response()->json([
                'status' => 'error',
                'message' => "عذراً، عدد الضيوف يتجاوز القدرة الاستيعابية القصوى للصالة وهي ({$venue->capacity}) شخص."
            ], 422);
        }

        // ------------------------------------------------------------
        // بداية خوارزمية ساعة الفراغ والتنظيف الإلزامية
        // ------------------------------------------------------------

        // تحويل أوقات الحجز الجديد إلى كائنات Carbon للتعامل معها برمجياً بسهولة
        $requestedStart = Carbon::parse($request->start_time);
        $requestedEnd = Carbon::parse($request->end_time);

        // إضافة "ساعة التنظيف الإلزامية" لوقت الانتهاء المطلوب
        $requestedEndWithCleaning = (clone $requestedEnd)->addHour();

        // جلب جميع الحجوزات الحية لنفس الصالة ونفس التاريخ
        $existingEvents = Event::where('venue_id', $venue->id)
            ->where('date', $request->date)
            ->whereIn('status', ['pending', 'venue_pending', 'vendor_pending', 'confirmed', 'paid'])
            ->get();

        foreach ($existingEvents as $existingEvent) {
            // 🔥 التعديل: استخدام Carbon::parse لمنع خطأ Trailing data نهائياً
            $existingStart = Carbon::parse($existingEvent->start_time);
            $existingEnd = Carbon::parse($existingEvent->end_time);

            // إضافة ساعة التنظيف للحجز القديم أيضاً لضمان عدم التداخل
            $existingEndWithCleaning = (clone $existingEnd)->addHour();

            // 💡 تصحيح الشرط الرياضي لفحص التداخل بين فترتين بشكل صحيح:
            // يتداخلان إذا كان (وقت البدء الجديد < وقت النهاية القديم مع التنظيف) وَ (وقت النهاية الجديد مع التنظيف > وقت البدء القديم)
            if ($requestedStart->lt($existingEndWithCleaning) && $requestedEndWithCleaning->gt($existingStart)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'عذراً، هذا الوقت غير متاح للحجز. الصالة مشغولة بحجز آخر أو تقع ضمن ساعة التنظيف الإلزامية المخصصة للصالة.',
                    'conflict_event' => [
                        'start_time' => $existingStart->format('H:i'), // عرض منسق بدون ثواني للزبون
                        'end_time' => $existingEnd->format('H:i'),
                        'available_after' => $existingEndWithCleaning->format('H:i')
                    ]
                ], 422);
            }
        }

        // ------------------------------------------------------------
        // نهاية خوارزمية ساعة الفراغ والتنظيف
        // ------------------------------------------------------------

        // 4. حساب السعر الإجمالي (اعتماد سعر الصالة الأساسي للطلب)
        $totalPrice = $venue->price;

        // 5. جلب الزبون الحالي من التوكن وحفظ سجل المناسبة
        $user = $request->user();

        $event = Event::create([
            'customer_id' => $user->id,
            'venue_id' => $venue->id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'guests_count' => $request->guests_count,
            'total_price' => $totalPrice,
            'note' => $request->note,
            'status' => 'pending', // الحالة الابتدائية للطلب
        ]);

        // 6. إرجاع استجابة النجاح للفرونت إند
        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب حجز المناسبة بنجاح، بانتظار موافقة صاحب الصالة.',
            'data' => $event
        ], 201);
    }
}
