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
        $requestedStart = Carbon::createFromFormat('H:i', $request->start_time);
        $requestedEnd = Carbon::createFromFormat('H:i', $request->end_time);

        // إضافة "ساعة التنظيف الإلزامية" لوقت الانتهاء المطلوب
        $requestedEndWithCleaning = (clone $requestedEnd)->addHour();

        // جلب جميع الحجوزات الحية (التي لم تُرفض أو تُلتغى) لنفس الصالة ونفس التاريخ
        $existingEvents = Event::where('venue_id', $venue->id)
            ->where('date', $request->date)
            ->whereIn('status', ['pending', 'venue_pending', 'vendor_pending', 'confirmed', 'paid'])
            ->get();

        foreach ($existingEvents as $existingEvent) {
            // تحويل أوقات الحجز الموجود مسبقاً في قاعدة البيانات
            $existingStart = Carbon::createFromFormat('H:i', $existingEvent->start_time);
            $existingEnd = Carbon::createFromFormat('H:i', $existingEvent->end_time);

            // إضافة ساعة التنظيف للحجز القديم أيضاً لضمان عدم التداخل مع الحجز الجديد
            $existingEndWithCleaning = (clone $existingEnd)->addHour();

            // فحص التداخل الرياضي المنطقي بين الفترتين الزمنيتين (مع احتساب ساعات التنظيف لكليهما)
            // الشرط: يتداخل الحجزان إذا كان (وقت بدء أحدهما يقع قبل نهاية الآخر المضاف لها ساعة التنظيف)
            if ($requestedStart->lt($existingEndWithCleaning) && $requestedEndWithCleaning->gt($existingStart)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'عذراً، هذا الوقت غير متاح للحجز. الصالة مشغولة بحجز آخر أو تقع ضمن ساعة التنظيف الإلزامية المخصصة للصالة.',
                    'conflict_event' => [
                        'start_time' => $existingEvent->start_time,
                        'end_time' => $existingEvent->end_time,
                        'available_after' => $existingEndWithCleaning->format('H:i') // يوضح للزبون متى تفضى الصالة تماماً
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
