<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Event;
use App\Models\Payment;

class InvoicePaidNotification extends Notification
{
    use Queueable;

    protected $event;
    protected $payment;

    public function __construct(Event $event, Payment $payment)
    {
        $this->event = $event;
        $this->payment = $payment;
    }

    public function via($notifiable)
    {
        return ['database']; // 💾 حفظ في قاعدة البيانات لصاحب الصالة والموردين
    }

    public function toArray($notifiable)
    {
        return [
            'event_id' => $this->event->id,
            'payment_id' => $this->payment->id,
            'type' => 'invoice_paid',
            'title' => 'تم استلام الدفعة 💰',
            'message' => "قام الزبون بدفع فاتورة مناسبة ({$this->event->event_name}) بمبلغ ({$this->payment->amount}) بنجاح، الحجز أصبح معتمداً ومدفوعاً بالكامل.",
        ];
    }
}
