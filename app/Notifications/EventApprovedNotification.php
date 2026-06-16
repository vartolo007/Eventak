<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventApprovedNotification extends Notification
{
    use Queueable;
    protected $event;

    public function __construct($event) { $this->event = $event; }

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array {
        return [
            'event_id' => $this->event->id,
            'title' => 'تأكيد الحجز الخاص بك',
            'message' => "تمت الموافقة على حجز الصالة لمناسبتك من قبل الإدارة، والطلب الآن قيد التنسيق مع الموردين لتجهيز الخدمات." . $this->event->date,
            'type' => 'booking_approved'
        ];
    }
}
