<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use App\Notifications\NewEventNotification;
use App\Notifications\EventApprovedNotification;
use App\Notifications\EventRejectedNotification;


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
        // تحويل أوقات الحجز الجديد إلى كائنات Carbon للتعامل معها برمجياً بسهولة ومرونة
        $requestedStart = Carbon::parse($request->start_time);
        $requestedEnd = Carbon::parse($request->end_time);

        // جلب جميع الحجوزات الحية لنفس الصالة ونفس التاريخ من قاعدة البيانات
        $existingEvents = Event::where('venue_id', $venue->id)
            ->where('date', $request->date)
            ->whereIn('status', ['pending', 'venue_pending', 'vendor_pending', 'confirmed', 'paid'])
            ->get();

        foreach ($existingEvents as $existingEvent) {
            // استخدام Carbon::parse لمنع خطأ Trailing data (500 Error) الناتج عن اختلاف صيغة الثواني في MySQL
            $existingStart = Carbon::parse($existingEvent->start_time);
            $existingEnd = Carbon::parse($existingEvent->end_time);

            // إضافة "ساعة التنظيف الإلزامية" بعد وقت انتهاء الحجز القديم المخزن فقط لحمايته
            $existingEndWithCleaning = (clone $existingEnd)->addHour();

            // 💡 فحص التداخل الرياضي والمنطقي بين الفترتين الزمنيتين بشكل صحيح:
            // يتداخل الحجزان إذا بدأ الحجز الجديد قبل نهاية القديم (المضاف لها ساعة التنظيف) وَ انتهى الحجز الجديد بعد بداية الحجز القديم
            if ($requestedStart->lt($existingEndWithCleaning) && $requestedEnd->gt($existingStart)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'عذراً، هذا الوقت غير متاح للحجز. الصالة مشغولة بحجز آخر أو تقع ضمن ساعة التنظيف الإلزامية للمناسبة السابقة.',
                    'conflict_event' => [
                        'start_time' => $existingStart->format('H:i'), // عرض وقت البدء منسق بدون ثواني للزبون
                        'end_time' => $existingEnd->format('H:i'),     // عرض وقت الانتهاء منسق بدون ثواني للزبون
                        'available_after' => $existingEndWithCleaning->format('H:i') // يوضح للزبون متى تفضى الصالة تماماً بعد انتهاء ساعة التنظيف
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
        // 🔔 1️⃣ المكان الصحيح الأول: إرسال إشعار لصاحب الصالة فور نجاح الحجز يدوياً
        $venueOwner = $venue->owner; // جلب موديل المستخدم الخاص بصاحب الصالة (تأكدي من وجود علاقة owner في موديل Venue)
        if ($venueOwner) {
            $venueOwner->notify(new NewEventNotification($event));
        }

        // 6. إرجاع استجابة النجاح للفرونت إند
        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب حجز المناسبة بنجاح، بانتظار موافقة صاحب الصالة.',
            'data' => $event
        ], 201);
    }

    public function accept($id)
    {
        $event = Event::findOrFail($id);
        $event->update(['status' => 'confirmed']); // أو حسب الحالة المعتمدة لديكِ 'approved'


        $customer = $event->customer; // جلب الزبون (تأكدي أن اسم العلاقة في موديل Event هو customer أو user واضبطيها هنا)
        if ($customer) {
            $customer->notify(new EventApprovedNotification($event));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تأكيد الحجز بنجاح، وإرسال إشعار للزبون.'
        ]);
    }


    // دالة رفض صاحب الصالة للحجز

    public function reject($id)
    {
        $event = Event::findOrFail($id);
        $event->update(['status' => 'rejected']);

        $customer = $event->customer;
        if ($customer) {
            $customer->notify(new EventRejectedNotification($event));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفض طلب الحجز بنجاح، وإشعار الزبون بالنتيجة.'
        ]);
    }

    //1. جلب كافة الحجوزات الخاصة بالزبون الحالي

    public function myRequests(Request $request)
    {
        $user = $request->user();

        // جلب الحجوزات وترتيبها من الأحدث إلى الأقدم مع جلب بيانات الصالة المرتبطة
        $requests = Event::with('venue')
            ->where('customer_id', $user->id) // الفلترة بحسب الـ customer_id الخاص بالزبون الحالي
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }


    // 2. جلب تفاصيل حجز واحد معين

    public function showRequest(Request $request, $id)
    {
        $user = $request->user();

        // جلب الحجز بشرط أن يكون تابعاً للزبون الحالي لحماية الخصوصية والأمان
        $eventRequest = Event::with('venue')
            ->where('customer_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$eventRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'عذراً، الطلب غير موجود أو لا تملك صلاحية الوصول إليه.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $eventRequest
        ]);
    }
}
