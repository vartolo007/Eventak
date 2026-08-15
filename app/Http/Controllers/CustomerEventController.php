<?php
namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Venue;
use App\Notifications\EventCancelledByCustomerNotification;
use App\Notifications\EventRejectedNotification; // استخدام الإشعار المعدل
use App\Notifications\NewEventNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerEventController extends Controller
{

// ... (داخل كلاس CustomerController) ...

/**
 * جلب وعرض كافة الصالات المتاحة للجمهور (العملاء)
 * هذه الدالة عامة ولا تتطلب توكن.
 *
 * @return \Illuminate\Http\JsonResponse
 */
    public function listVenues()
    {
        // جلب الصالات التي حالتها 'active'
        // مع تحميل بيانات المالك (owner) فقط (id, name, email, phone)
        $venues = Venue::where('status', 'active')
            ->with('owner:id,name,email,phone')
            ->orderBy('created_at', 'desc') // ترتيب حسب الأحدث
            ->get();


            
            return response()->json([
            'status' => 'success',
            'count'  => $venues->count(),
            'data'   => $venues,
        ], 200);
    }

// ... (الدوال الأخرى الموجودة لديك في CustomerController) ...

    /**
     * محرك إنشاء مناسبة جديدة مع خدمات وفاتورة
     */
    public function store(Request $request)
    {
        // Step 1: التحقق من المدخلات
        $validatedData = $request->validate([
            // Step 1: تفاصيل المناسبة
            'event_name'            => 'required|string|max:255',
            'event_type'            => 'required|string|max:255',

            // Step 2: المكان والتاريخ
            'venue_id'              => [
                'required',
                Rule::exists('venues', 'id')->where('status', 'active'),
            ],
            'date'                  => 'required|date|after_or_equal:today',
            'start_time'            => 'required|date_format:H:i',
            'end_time'              => 'required|date_format:H:i|after:start_time',
            'guests_count'          => 'required|integer|min:1',

            // Step 3: الخدمات المختارة
            'services'              => 'nullable|array',
            'services.*.service_id' => [
                'required_with:services',
                Rule::exists('services', 'id')->where('status', 'active'),
            ],
            'services.*.quantity'   => 'required_with:services|integer|min:1',

            'note'                  => 'nullable|string',
        ]);

        $user  = $request->user();
        $venue = Venue::where('status', 'active')->findOrFail($request->venue_id);

        // التحقق من القدرة الاستيعابية
        if ($request->guests_count > $venue->capacity) {
            return response()->json([
                'status'  => 'error',
                'message' => "عدد الضيوف يتجاوز القدرة الاستيعابية ({$venue->capacity}) شخص.",
            ], 422);
        }

        // ------------------------------------------------------------
        // بداية خوارزمية ساعة الفراغ والتنظيف الإلزامية
        // ------------------------------------------------------------

        // تحويل أوقات الحجز الجديد إلى كائنات Carbon للتعامل معها برمجياً بسهولة
        // تحويل أوقات الحجز الجديد إلى كائنات Carbon للتعامل معها برمجياً بسهولة ومرونة
        $requestedStart = Carbon::parse($request->start_time);
        $requestedEnd   = Carbon::parse($request->end_time);

        // جلب جميع الحجوزات الحية لنفس الصالة ونفس التاريخ من قاعدة البيانات
        $existingEvents = Event::where('venue_id', $venue->id)
            ->where('date', $request->date)
            ->whereIn('status', ['vendor_pending', 'confirmed', 'paid']) // تم تعديل هذا السطر
            ->get();

        foreach ($existingEvents as $existingEvent) {
            // استخدام Carbon::parse لمنع خطأ Trailing data (500 Error) الناتج عن اختلاف صيغة الثواني في MySQL
            $existingStart           = Carbon::parse($existingEvent->start_time);
            $existingEnd             = Carbon::parse($existingEvent->end_time);
            $existingEndWithCleaning = (clone $existingEnd)->addHour();

            $requestedEndWithCleaning = (clone $requestedEnd)->addHour();

            // إضافة "ساعة التنظيف الإلزامية" بعد وقت انتهاء الحجز القديم المخزن فقط لحمايته


            // 💡 فحص التداخل الرياضي والمنطقي بين الفترتين الزمنيتين بشكل صحيح:
            // يتداخل الحجزان إذا بدأ أي منهما قبل نهاية الآخر بعد احتساب ساعة التنظيف لكليهما
            if ($requestedStart->lt($existingEndWithCleaning) && $requestedEndWithCleaning->gt($existingStart)) {
                return response()->json([
                    'status'         => 'error',
                    'message'        => 'عذراً، هذا الوقت غير متاح للحجز. الصالة مشغولة بحجز آخر أو تقع ضمن ساعة التنظيف الإلزامية للمناسبة السابقة.',
                    'conflict_event' => [
                        'start_time'      => $existingStart->format('H:i'),           // عرض وقت البدء منسق بدون ثواني للزبون
                        'end_time'        => $existingEnd->format('H:i'),             // عرض وقت الانتهاء منسق بدون ثواني للزبون
                        'available_after' => $existingEndWithCleaning->format('H:i'), // يوضح للزبون متى تفضى الصالة تماماً بعد انتهاء ساعة التنظيف
                    ],
                ], 422);
            }
        }

        // ------------------------------------------------------------
        // نهاية خوارزمية ساعة الفراغ والتنظيف
        // ------------------------------------------------------------

        // 4. حساب السعر الإجمالي (اعتماد سعر الصالة الأساسي للطلب)
        $totalPrice   = $venue->price;
        $servicesData = [];
        // معالجة الخدمات المختارة
        if (! empty($request->services)) {
            foreach ($request->services as $serviceItem) {
                $service       = Service::where('status', 'active')->findOrFail($serviceItem['service_id']);
                $servicePrice  = $service->price * $serviceItem['quantity'];
                $totalPrice   += $servicePrice;

                $servicesData[] = [
                    'service_id' => $service->id,
                    'quantity'   => $serviceItem['quantity'],
                    'price'      => $service->price,
                    'status'     => 'pending',
                ];
            }
        }

        // إنشاء الفاتورة والمناسبة وربط الخدمات ضمن Transaction واحدة
        // (لو فشلت أي خطوة يتراجع كل شيء معاً ولا تبقى فاتورة يتيمة بلا مناسبة)
        [$invoice, $event] = DB::transaction(function () use ($request, $user, $venue, $totalPrice, $servicesData) {
            // إنشاء الفاتورة
            $invoice = Invoice::create([
                'venue_price'    => $venue->price,
                'services_total' => $totalPrice - $venue->price,
                'total_amount'   => $totalPrice,
                'status'         => 'pending',
            ]);

            // إنشاء المناسبة
            $event = Event::create([
                'customer_id'  => $user->id,
                'event_name'   => $request->event_name,
                'event_type'   => $request->event_type,
                'venue_id'     => $venue->id,
                'date'         => $request->date,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'guests_count' => $request->guests_count,
                'total_price'  => $totalPrice,
                'note'         => $request->note,
                'invoice_id'   => $invoice->id,
                'status'       => 'pending',
            ]);

            // ربط الخدمات بالمناسبة
            if (! empty($servicesData)) {
                foreach ($servicesData as $service) {
                    $event->services()->attach($service['service_id'], [
                        'quantity' => $service['quantity'],
                        'price'    => $service['price'],
                        'status'   => 'pending',
                    ]);
                }
            }

            // تحديث الفاتورة بـ event_id
            $invoice->update(['event_id' => $event->id]);

            return [$invoice, $event];
        });

        // إرسال إشعار لصاحب الصالة
        $venueOwner = $venue->owner;
        if ($venueOwner) {
            $venueOwner->notify(new NewEventNotification($event));
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إنشاء المناسبة بنجاح بانتظار الموافقة.',
            'data'    => [
                'event_id'       => $event->id,
                'event_name'     => $event->event_name,
                'event_type'     => $event->event_type,
                'venue_name'     => $venue->name,
                'total_price'    => $totalPrice,
                'invoice_id'     => $invoice->id,
                'services_count' => count($servicesData),
            ],
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
            'data'   => $requests,
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

        if (! $eventRequest) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، الطلب غير موجود أو لا تملك صلاحية الوصول إليه.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $eventRequest,
        ]);
    }

    /**
     * 3. إلغاء الحجز من قبل الزبون (مع استرجاع متدرج للمبلغ إذا كان مدفوعاً)
     *
     * سياسة الاسترجاع للحجوزات المدفوعة:
     * - أكثر من 7 أيام قبل المناسبة  -> استرجاع 100%
     * - بين 48 ساعة و 7 أيام          -> استرجاع 50%
     * - أقل من 48 ساعة                -> الإلغاء مسموح بدون أي استرجاع
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $event = Event::with(['venue.owner', 'invoice'])
            ->where('customer_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $event) {
            return response()->json([
                'status'  => 'error',
                'message' => 'عذراً، الحجز غير موجود أو لا تملك صلاحية الوصول إليه.',
            ], 404);
        }

        // لا يمكن إلغاء حجز مكتمل أو ملغى مسبقاً
        if (in_array($event->status, ['completed', 'cancelled'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لا يمكن إلغاء هذا الحجز لأنه مكتمل أو ملغى بالفعل.',
            ], 422);
        }

        $refundAmount  = null;
        $refundPercent = null;

        // إذا كان الحجز مدفوعاً نحسب المبلغ المسترجع بحسب الوقت المتبقي على المناسبة
        if ($event->status === 'paid') {
            $eventStart = Carbon::parse($event->date . ' ' . $event->start_time);
            $hoursLeft  = now()->diffInHours($eventStart, false);

            if ($hoursLeft >= 168) { // أكثر من 7 أيام
                $refundPercent = 100;
            } elseif ($hoursLeft >= 48) { // بين 48 ساعة و 7 أيام
                $refundPercent = 50;
            } else { // أقل من 48 ساعة (أو موعد فائت)
                $refundPercent = 0;
            }

            $payment = Payment::where('invoice_id', $event->invoice_id)
                ->where('status', 'success')
                ->first();

            if (! $payment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'تعذر العثور على سجل الدفع المرتبط بهذا الحجز، يرجى مراجعة الإدارة.',
                ], 422);
            }

            $refundAmount = round($payment->amount * $refundPercent / 100, 2);

            DB::transaction(function () use ($event, $payment, $refundAmount, $refundPercent) {
                // تحديث سجل الدفع: refunded مع توثيق المبلغ المسترجع (سيميوليشن)
                if ($refundAmount > 0) {
                    $payment->update([
                        'status'        => 'refunded',
                        'refund_amount' => $refundAmount,
                        'refunded_at'   => now(),
                    ]);
                    $event->invoice->update(['status' => 'refunded']);
                }
                // إذا كان الاسترجاع 0% يبقى الدفع success والفاتورة paid (المبلغ محتجز بالكامل)

                $event->update([
                    'status'           => 'cancelled',
                    'rejection_reason' => "ألغى الزبون الحجز، ونسبة الاسترجاع المطبقة {$refundPercent}% بحسب سياسة الإلغاء.",
                ]);
            });
        } else {
            // حجز غير مدفوع بعد (pending / vendor_pending / confirmed): إلغاء مباشر
            $event->update([
                'status'           => 'cancelled',
                'rejection_reason' => 'ألغى الزبون الحجز قبل الدفع.',
            ]);
        }

        // 🔔 إشعار صاحب الصالة بالإلغاء
        $venueOwner = $event->venue?->owner;
        if ($venueOwner) {
            $venueOwner->notify(new EventCancelledByCustomerNotification($event, $refundAmount));
        }

        // 🔔 إشعار كل مورد مشارك بخدمات هذه المناسبة (مرة واحدة لكل مورد)
        $vendors = $event->services()->with('vendor')->get()
            ->pluck('vendor')
            ->filter()
            ->unique('id');

        foreach ($vendors as $vendor) {
            $vendor->notify(new EventCancelledByCustomerNotification($event, $refundAmount));
        }

        // --- بداية: منطق إشعار الحجوزات المتضاربة السابقة بأن الصالة أصبحت متاحة ---
        // هذا المنطق ينطبق فقط إذا كان الحجز الملغى حالياً هو الحجز الذي تسبب في إلغاء حجوزات أخرى سابقاً.

        // تعريف النمط لسبب الرفض الذي يشير إلى تضارب مع هذا الحجز تحديداً
        $rejectionPattern = '%(ID: ' . $event->id . ')%';

        // البحث عن الحجوزات التي تم إلغاؤها سابقاً بسبب تضارب مع الحجز الذي تم إلغاؤه الآن
        $previouslyConflictingEvents = Event::where('venue_id', $event->venue_id)
            ->where('date', $event->date)
            ->where('id', '!=', $event->id) // استبعاد الحجز الحالي الذي يتم إلغاؤه
            ->where('status', 'cancelled')  // التأكد من أنها لا تزال في حالة ملغاة
            ->where('rejection_reason', 'like', $rejectionPattern)
            ->with('customer', 'venue') // تحميل بيانات الزبون والصالة للإشعار
            ->get();

        foreach ($previouslyConflictingEvents as $conflictingEvent) {
            if ($conflictingEvent->customer) {
                // إشعار الزبون بأن الصالة أصبحت متاحة الآن
                $conflictingEvent->customer->notify(new EventRejectedNotification(
                    $conflictingEvent,
                    'venue_available',
                    'أهلاً بك! الصالة ' . ($conflictingEvent->venue->name ?? '') . ' أصبحت متاحة الآن في تاريخ ' . $conflictingEvent->date . ' بسبب إلغاء حجز سابق. يمكنك مراجعة طلبك أو تقديم طلب جديد.'
                ));
            }
        }
        // --- نهاية: منطق إشعار الحجوزات المتضاربة السابقة ---


        return response()->json([
            'status'  => 'success',
            'message' => is_null($refundPercent)
                ? 'تم إلغاء الحجز بنجاح.'
                : ($refundAmount > 0
                    ? "تم إلغاء الحجز بنجاح، وسيتم استرجاع مبلغ ({$refundAmount}) أي ما يعادل {$refundPercent}% من قيمة الفاتورة."
                    : 'تم إلغاء الحجز بنجاح، ولا يوجد مبلغ مسترجع لأن الإلغاء تم قبل أقل من 48 ساعة من الموعد.'),
            'data' => [
                'event_id'       => $event->id,
                'event_status'   => $event->status,
                'refund_percent' => $refundPercent,
                'refund_amount'  => $refundAmount,
            ],
        ], 200);
    }
}
