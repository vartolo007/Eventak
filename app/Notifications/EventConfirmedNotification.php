<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventConfirmedNotification extends Notification
{
    use Queueable;
    protected $event;

    public function __construct($event) { $this->event = $event; }

    public function via($notifiable): array { return ['database', FcmChannel::class]; }

    public function toArray($notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'title' => __('messages.notifications.booking_confirmed_title'),
            'message' => __('messages.notifications.booking_confirmed_message', ['event_name' => $this->event->event_name, 'date' => $this->event->date]),
            'type' => 'booking_confirmed'
        ];
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('messages.notifications.booking_confirmed_title'),
            'message' => __('messages.notifications.booking_confirmed_message', ['event_name' => $this->event->event_name, 'date' => $this->event->date]),
            'data' => ['type' => 'booking_confirmed', 'event_id' => $this->event->id]
        ];
    }
}
