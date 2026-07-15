<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventConfirmedNotification extends Notification
{
    use Queueable;
    protected $event;

    public function __construct($event) { $this->event = $event; }

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array {
        return [
            'event_id' => $this->event->id,
            'title' => 'حجزك مؤكد وجاهز للدفع ✅',
            'message' => "اكتملت الموافقات على مناسبتك ({$this->event->event_name}) بتاريخ {$this->event->date}، وأصبح الحجز مؤكداً بالكامل وبانتظار دفع الفاتورة لاعتماده.",
            'type' => 'booking_confirmed'
        ];
    }
}
