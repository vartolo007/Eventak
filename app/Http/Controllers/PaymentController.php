<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Invoice;

class PaymentController extends Controller
{
    // إنشاء عملية دفع جديدة
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_method' => 'required|in:credit_card,debit_card,paypal',
            'amount' => 'required|numeric|min:0',
            'transaction_id' => 'nullable|string',
        ]);

        // التحقق من أن الفاتورة تابعة للزبون
        $invoice = Invoice::findOrFail($validated['invoice_id']);
        if ($invoice->event->customer_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية الدفع لهذه الفاتورة'
            ], 403);
        }

        // التحقق من أن المناسبة مؤكدة وجاهزة للدفع فعلياً
        if ($invoice->event->status !== 'confirmed') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكنك دفع قيمة الفاتورة حالياً، الحجز لم يتم تأكيده بعد من الإدارة أو الموردين.'
            ], 422);
        }

        // التحقق من أن المبلغ يطابق الفاتورة
        if ($validated['amount'] != $invoice->total_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'المبلغ غير متطابق مع الفاتورة'
            ], 422);
        }

        // إنشاء سجل الدفع
        $payment = Payment::create([
            'invoice_id' => $validated['invoice_id'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'transaction_id' => $validated['transaction_id'] ?? 'txn_' . time(),
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

        return response()->json([
            'status' => 'success',
            'message' => 'تم الدفع بنجاح',
            'data' => [
                'payment_id' => $payment->id,
                'event_id' => $event->id,
                'amount' => $payment->amount,
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
