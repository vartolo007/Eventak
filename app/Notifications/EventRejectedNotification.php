<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Event;

class EventRejectedNotification extends Notification
{
    use Queueable;
    protected $event;
    protected $notificationType; // 'rejected' or 'venue_available'
    protected $customMessage;

    public function __construct(Event $event, string $notificationType = 'rejected', string $customMessage = null)
    {
        $this->event = $event;
        $this->notificationType = $notificationType;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Firebase معلّق - كان: ['database', FcmChannel::class]
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $locale = $notifiable->locale ?? config('app.locale');
        $eventName = $this->event->getTranslation('event_name', $locale);
        $venueName = $this->event->venue
            ? $this->event->venue->getTranslation('name', $locale)
            : __('messages.venue.default_name', [], $locale);

        if ($this->notificationType === 'venue_available') {
            return [
                'type' => 'venue_available',
                'event_id' => $this->event->id,
                'event_name' => $eventName,
                'venue_name' => $venueName,
                'date' => $this->event->date,
                'message' => $this->customMessage ?? __('messages.notifications.venue_available_message', [
                    'venue_name' => $venueName,
                    'date' => $this->event->date,
                ], $locale),
            ];
        }

        return [
            'type' => 'event_rejected',
            'event_id' => $this->event->id,
            'event_name' => $eventName,
            'message' => $this->customMessage ?? __('messages.notifications.event_rejected_message', [
                'event_name' => $eventName,
                'date' => $this->event->date,
            ], $locale),
        ];
    }

    // Firebase معلّق - toFcm
    // public function toFcm(object $notifiable): array
    // {
    //     $locale = $notifiable->locale ?? config('app.locale');
    //     $eventName = $this->event->getTranslation('event_name', $locale);
    //     $venueName = $this->event->venue
    //         ? $this->event->venue->getTranslation('name', $locale)
    //         : __('messages.venue.default_name', [], $locale);
    //     if ($this->notificationType === 'venue_available') {
    //         return [
    //             'title' => __('messages.notifications.venue_available_title', [], $locale),
    //             'message' => $this->customMessage ?? __('messages.notifications.venue_available_message', [
    //                 'venue_name' => $venueName, 'date' => $this->event->date,
    //             ], $locale),
    //             'data' => ['type' => 'venue_available', 'event_id' => $this->event->id,
    //                 'event_name' => $eventName, 'venue_name' => $venueName, 'date' => $this->event->date]
    //         ];
    //     }
    //     return [
    //         'title' => __('messages.notifications.event_rejected_title', [], $locale),
    //         'message' => $this->customMessage ?? __('messages.notifications.event_rejected_message', [
    //             'event_name' => $eventName, 'date' => $this->event->date,
    //         ], $locale),
    //         'data' => ['type' => 'event_rejected', 'event_id' => $this->event->id]
    //     ];
    // }
}
