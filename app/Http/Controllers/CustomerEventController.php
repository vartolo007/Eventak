<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Venue;
use App\Models\Service;
use App\Models\Invoice;
use Carbon\Carbon;
use App\Notifications\NewEventNotification;
use App\Notifications\EventApprovedNotification;
use App\Notifications\EventRejectedNotification;


class CustomerEventController extends Controller
{
    /**
     * محرك إنشاء مناسبة جديدة مع خدمات وفاتورة
     */
    public function store(Request $request)
    {
        // Step 1: التحقق من المدخلات
        $validatedData = $request->validate([
            // Step 1: تفاصيل المناسبة
            'event_name' => 'required|string|max:255',
            'event_type' => 'required|string|max:255',

            // Step 2: المكان والتاريخ
            'venue_id' => 'required|exists:venues,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'guests_count' => 'required|integer|min:1',

            // Step 3: الخدمات المختارة
            'services' => 'nullable|array',
            'services.*.service_id' => 'required_with:services|exists:services,id',
            'services.*.quantity' => 'required_with:services|integer|min:1',

            'note' => 'nullable|string',
        ]);

        $user = $request->user();
        $venue = Venue::findOrFail($request->venue_id);

        // التحقق من القدرة الاستيعابية
        if ($request->guests_count > $venue->capacity) {
            return response()->json([
                'status' => 'error',
                'message' => "عدد الضيوف يتجاوز القدرة الاستيعابية ({$venue->capacity}) شخص."
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
        $servicesData = [];
        // معالجة الخدمات المختارة
        if (!empty($request->services)) {
            foreach ($request->services as $serviceItem) {
                $service = Service::findOrFail($serviceItem['service_id']);
                $servicePrice = $service->price * $serviceItem['quantity'];
                $totalPrice += $servicePrice;

                $servicesData[] = [
                    'service_id' => $service->id,
                    'quantity' => $serviceItem['quantity'],
                    'price' => $service->price,
                    'status' => 'pending'
                ];
            }
        }

        // إنشاء الفاتورة
        $invoice = Invoice::create([
            'venue_price' => $venue->price,
            'services_total' => $totalPrice - $venue->price,
            'total_amount' => $totalPrice,
            'status' => 'pending'
        ]);

        // إنشاء المناسبة
        $event = Event::create([
            'customer_id' => $user->id,
            'event_name' => $request->event_name,
            'event_type' => $request->event_type,
            'venue_id' => $venue->id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'guests_count' => $request->guests_count,
            'total_price' => $totalPrice,
            'note' => $request->note,
            'invoice_id' => $invoice->id,
            'status' => 'pending'
        ]);

        // ربط الخدمات بالمناسبة
        if (!empty($servicesData)) {
            foreach ($servicesData as $service) {
                $event->services()->attach($service['service_id'], [
                    'quantity' => $service['quantity'],
                    'price' => $service['price'],
                    'status' => 'pending'
                ]);
            }
        }

        // تحديث الفاتورة بـ event_id
        $invoice->update(['event_id' => $event->id]);

        // إرسال إشعار لصاحب الصالة
        $venueOwner = $venue->owner;
        if ($venueOwner) {
            $venueOwner->notify(new NewEventNotification($event));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء المناسبة بنجاح بانتظار الموافقة.',
            'data' => [
                'event_id' => $event->id,
                'event_name' => $event->event_name,
                'event_type' => $event->event_type,
                'venue_name' => $venue->name,
                'total_price' => $totalPrice,
                'invoice_id' => $invoice->id,
                'services_count' => count($servicesData)
            ]
        ], 201);
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
