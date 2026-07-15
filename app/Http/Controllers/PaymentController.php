<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Invoice;
use App\Notifications\InvoicePaidNotification;

class PaymentController extends Controller
{
    // إنشاء عملية دفع جديدة (محاكاة بوابة دفع - Simulation)
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,cash',

            // بيانات البطاقة الوهمية (مطلوبة فقط عند الدفع بالبطاقة - تحقق شكلي بدون تخزين)
            'card_number' => 'required_if:payment_method,credit_card,debit_card|digits_between:13,19',
            'card_holder' => 'required_if:payment_method,credit_card,debit_card|string|max:255',
            'expiry_month' => 'required_if:payment_method,credit_card,debit_card|integer|between:1,12',
            'expiry_year' => 'required_if:payment_method,credit_card,debit_card|integer|min:' . now()->year,
            'cvv' => 'required_if:payment_method,credit_card,debit_card|digits_between:3,4',

            // إيميل الباي بال (مطلوب فقط عند اختيار paypal)
            'paypal_email' => 'required_if:payment_method,paypal|email',
        ]);

        // التحقق من أن الفاتورة تابعة للزبون
        $invoice = Invoice::findOrFail($validated['invoice_id']);
        if ($invoice->event->customer_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية الدفع لهذه الفاتورة'
            ], 403);
        }

        // منع الدفع المزدوج: إذا الفاتورة مدفوعة أو يوجد دفع ناجح سابق لها
        // (هذا الفحص قبل فحص حالة المناسبة حتى تصل رسالة واضحة لمن دفع مسبقاً)
        $alreadyPaid = $invoice->status === 'paid'
            || Payment::where('invoice_id', $invoice->id)->where('status', 'success')->exists();

        if ($alreadyPaid) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذه الفاتورة مدفوعة مسبقاً، لا يمكن الدفع مرة أخرى.'
            ], 422);
        }

        // التحقق من أن المناسبة مؤكدة وجاهزة للدفع فعلياً
        if ($invoice->event->status !== 'confirmed') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكنك دفع قيمة الفاتورة حالياً، الحجز لم يتم تأكيده بعد من الإدارة أو الموردين.'
            ], 422);
        }

        // التحقق من انتهاء صلاحية البطاقة (الشهر والسنة معاً)
        if (in_array($validated['payment_method'], ['credit_card', 'debit_card'])) {
            if ($validated['expiry_year'] == now()->year && $validated['expiry_month'] < now()->month) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'البطاقة منتهية الصلاحية.'
                ], 422);
            }
        }

        // توليد رقم عملية وهمي (نخزن آخر 4 أرقام من البطاقة فقط للتوثيق - لا يتم تخزين رقم البطاقة كاملاً)
        $reference = in_array($validated['payment_method'], ['credit_card', 'debit_card'])
            ? substr($validated['card_number'], -4)
            : $validated['payment_method'];
        $transactionId = 'SIM-' . strtoupper(uniqid()) . '-' . $reference;

        // إنشاء سجل الدفع (المحاكاة تنجح دائماً، والمبلغ يؤخذ من الفاتورة نفسها وليس من الزبون)
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => $validated['payment_method'],
            'amount' => $invoice->total_amount,
            'transaction_id' => $transactionId,
            'status' => 'success',
            'paid_at' => now()
        ]);

        // تحديث حالة الفاتورة
        $invoice->update(['status' => 'paid']);

        // تحديث حالة المناسبة
        $event = $invoice->event;
        $event->update([
            'payment_id' => $payment->id,
            'status' => 'paid'
        ]);

        // 🔔 إشعار صاحب الصالة باستلام الدفعة
        $venueOwner = $event->venue?->owner;
        if ($venueOwner) {
            $venueOwner->notify(new InvoicePaidNotification($event, $payment));
        }

        // 🔔 إشعار كل مورد مشارك بخدمات هذه المناسبة (مرة واحدة لكل مورد حتى لو لديه أكثر من خدمة)
        $vendors = $event->services()->with('vendor')->get()
            ->pluck('vendor')
            ->filter()
            ->unique('id');

        foreach ($vendors as $vendor) {
            $vendor->notify(new InvoicePaidNotification($event, $payment));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم الدفع بنجاح',
            'data' => [
                'payment_id' => $payment->id,
                'event_id' => $event->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at
            ]
        ], 201);
    }

    // عرض سجل الدفع
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $payment = Payment::with('invoice.event')->findOrFail($id);

        // التحقق من الملكية
        if ($payment->invoice->event->customer_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية عرض هذا الدفع'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    // عرض سجل الدفع للفاتورة
    public function byInvoice(Request $request, $invoiceId)
    {
        $user = $request->user();
        $invoice = Invoice::findOrFail($invoiceId);

        // التحقق من الملكية
        if ($invoice->event->customer_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية عرض هذه الفاتورة'
            ], 403);
        }

        $payment = Payment::where('invoice_id', $invoiceId)->first();

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }
}
