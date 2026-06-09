<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventRejectedNotification extends Notification
{
    use Queueable;
    protected $event;

    public function __construct($event) { $this->event = $event; }

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array {
        return [
            'event_id' => $this->event->id,
            'title' => 'اعتذار عن الحجز',
            'message' => "نأسف، تم رفض طلب حجز الصالة الخاص بك بسبب تعارض أو عدم استيفاء الشروط.",
            'type' => 'booking_rejected'
        ];
    }
}
