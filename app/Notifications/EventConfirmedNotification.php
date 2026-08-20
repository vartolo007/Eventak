<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventConfirmedNotification extends Notification
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
            'title' => __('messages.notifications.booking_confirmed_title', [], $locale),
            'message' => __('messages.notifications.booking_confirmed_message', [
                'event_name' => $this->event->getTranslation('event_name', $locale),
                'date' => $this->event->date,
            ], $locale),
            'type' => 'booking_confirmed'
        ];
    }

    // Firebase معلّق - toFcm
    // public function toFcm(object $notifiable): array
    // {
    //     $locale = $notifiable->locale ?? config('app.locale');
    //     return [
    //         'title' => __('messages.notifications.booking_confirmed_title', [], $locale),
    //         'message' => __('messages.notifications.booking_confirmed_message', [
    //             'event_name' => $this->event->getTranslation('event_name', $locale),
    //             'date' => $this->event->date,
    //         ], $locale),
    //         'data' => ['type' => 'booking_confirmed', 'event_id' => $this->event->id]
    //     ];
    // }
}
