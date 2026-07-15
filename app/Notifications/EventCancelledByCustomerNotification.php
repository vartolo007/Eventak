<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Event;

class EventCancelledByCustomerNotification extends Notification
{
    use Queueable;

    protected $event;
    protected $refundAmount;

    public function __construct(Event $event, $refundAmount = null)
    {
        $this->event = $event;
        $this->refundAmount = $refundAmount;
    }

    public function via($notifiable): array
    {
        return ['database']; // 💾 حفظ في قاعدة البيانات لصاحب الصالة والموردين
    }

    public function toArray($notifiable): array
    {
        $message = "قام الزبون بإلغاء حجز مناسبة ({$this->event->event_name}) بتاريخ {$this->event->date}.";

        // إذا كان الحجز مدفوعاً نوضح قيمة المبلغ المسترجع للزبون بحسب سياسة الاسترجاع
        if (!is_null($this->refundAmount)) {
            $message .= $this->refundAmount > 0
                ? " تم استرجاع مبلغ ({$this->refundAmount}) للزبون بحسب سياسة الاسترجاع."
                : " لم يُسترجع أي مبلغ للزبون لأن الإلغاء تم قبل أقل من 48 ساعة من الموعد.";
        }

        return [
            'event_id' => $this->event->id,
            'type' => 'booking_cancelled_by_customer',
            'title' => 'تم إلغاء الحجز من قبل الزبون ❌',
            'message' => $message,
        ];
    }
}
