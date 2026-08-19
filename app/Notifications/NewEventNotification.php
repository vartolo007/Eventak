<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEventNotification extends Notification
{
    use Queueable;

    protected $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function via($notifiable): array
    {
        return ['database', FcmChannel::class]; // حفظ الإشعار في قاعدة البيانات ليقرأه الفرونت إند
    }

    public function toArray($notifiable): array
    {
        $locale = $notifiable->locale ?? config('app.locale');
        return [
            'event_id' => $this->event->id,
            'title' => __('messages.notifications.new_event_request_title', [], $locale),
            'message' => __('messages.notifications.new_event_request_message', [
                'event_name' => $this->event->getTranslation('event_name', $locale),
                'customer_name' => $this->event->customer->name,
            ], $locale),
            'type' => 'new_event_request'
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $locale = $notifiable->locale ?? config('app.locale');
        return [
            'title' => __('messages.notifications.new_event_request_title', [], $locale),
            'message' => __('messages.notifications.new_event_request_message', [
                'event_name' => $this->event->getTranslation('event_name', $locale),
                'customer_name' => $this->event->customer->name,
            ], $locale),
            'data' => ['type' => 'new_event_request', 'event_id' => $this->event->id]
        ];
    }
}
