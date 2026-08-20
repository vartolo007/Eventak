<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventApprovedNotification extends Notification
{
    use Queueable;
    protected $event;

    public function __construct($event) { $this->event = $event; }

    public function via($notifiable): array { return ['database']; } // Firebase معلّق

    public function toArray($notifiable): array
    {
        $locale = $notifiable->locale ?? config('app.locale');
        return [
            'event_id' => $this->event->id,
            'title' => __('messages.notifications.booking_approved_title', [], $locale),
            'message' => __('messages.notifications.booking_approved_message', ['date' => $this->event->date], $locale),
            'type' => 'booking_approved'
        ];
    }

    // Firebase معلّق - toFcm
    // public function toFcm(object $notifiable): array
    // {
    //     $locale = $notifiable->locale ?? config('app.locale');
    //     return [
    //         'title' => __('messages.notifications.booking_approved_title', [], $locale),
    //         'message' => __('messages.notifications.booking_approved_message', ['date' => $this->event->date], $locale),
    //         'data' => ['type' => 'booking_approved', 'event_id' => $this->event->id]
    //     ];
    // }
}
