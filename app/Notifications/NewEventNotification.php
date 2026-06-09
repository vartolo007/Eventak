<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEventNotification extends Notification
{
    use Queueable;

    protected $event;

    public function __construct($event) {
        $this->event = $event;
    }

    public function via($notifiable): array {
        return ['database']; // حفظ الإشعار في قاعدة البيانات ليقرأه الفرونت إند
    }

    public function toArray($notifiable): array {
        return [
            'event_id' => $this->event->id,
            'title' => 'طلب حجز جديد قيد الانتظار',
            'message' => "تم تقديم طلب حجز جديد لصالتك بتاريخ " . $this->event->date,
            'type' => 'new_booking'
        ];
    }
}
