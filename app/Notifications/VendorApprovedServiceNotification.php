<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Event;
use App\Models\Service;

class VendorApprovedServiceNotification extends Notification
{
    use Queueable;

    protected $event;
    protected $service;

    public function __construct(Event $event, Service $service)
    {
        $this->event = $event;
        $this->service = $service;
    }

    public function via($notifiable)
    {
        return ['database']; // Firebase معلّق - كان: ['database', FcmChannel::class]
    }

    public function toArray($notifiable)
    {
        $locale = $notifiable->locale ?? config('app.locale');
        return [
            'event_id' => $this->event->id,
            'service_id' => $this->service->id,
            'type' => 'vendor_service_approved',
            'title' => __('messages.notifications.vendor_service_approved_title', [], $locale),
            'message' => __('messages.notifications.vendor_service_approved_message', [
                'service_name' => $this->service->getTranslation('name', $locale),
                'event_name' => $this->event->getTranslation('event_name', $locale),
            ], $locale),
        ];
    }

    // Firebase معلّق - toFcm
    // public function toFcm(object $notifiable): array
    // {
    //     $locale = $notifiable->locale ?? config('app.locale');
    //     return [
    //         'title' => __('messages.notifications.vendor_service_approved_title', [], $locale),
    //         'message' => __('messages.notifications.vendor_service_approved_message', [
    //             'service_name' => $this->service->getTranslation('name', $locale),
    //             'event_name' => $this->event->getTranslation('event_name', $locale),
    //         ], $locale),
    //         'data' => [
    //             'type' => 'vendor_service_approved',
    //             'event_id' => $this->event->id,
    //             'service_id' => $this->service->id,
    //         ]
    //     ];
    // }
}
