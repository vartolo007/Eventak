<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventApprovedNotification extends Notification
{
    use Queueable;
    protected $event;

    public function __construct($event) { $this->event = $event; }

    public function via($notifiable): array { return ['database', FcmChannel::class]; }

    public function toArray($notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'title' => __('messages.notifications.booking_approved_title'),
            'message' => __('messages.notifications.booking_approved_message', ['date' => $this->event->date]),
            'type' => 'booking_approved'
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
            'title' => __('messages.notifications.booking_approved_title'),
            'message' => __('messages.notifications.booking_approved_message', ['date' => $this->event->date]),
            'data' => ['type' => 'booking_approved', 'event_id' => $this->event->id]
        ];
    }
}
