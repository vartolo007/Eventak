<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Event;
use App\Models\Service;

class VendorRejectedServiceNotification extends Notification
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
        return ['database', FcmChannel::class]; // 💾 حفظ في قاعدة البيانات للزبون
    }

    public function toArray($notifiable)
    {
        $data = [
            'event_id' => $this->event->id,
            'service_id' => $this->service->id,
            'type' => 'vendor_service_rejected',
            'title' => __('messages.notifications.vendor_service_rejected_title'),
            'message' => __('messages.notifications.vendor_service_rejected_message', [
                'service_name' => $this->service->name,
                'event_name' => $this->event->event_name,
            ]),
        ];

        return $data;
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('messages.notifications.vendor_service_rejected_title'),
            'message' => __('messages.notifications.vendor_service_rejected_message', [
                'service_name' => $this->service->name,
                'event_name' => $this->event->event_name,
            ]),
            'data' => [
                'type' => 'vendor_service_rejected',
                'event_id' => $this->event->id,
                'service_id' => $this->service->id,
            ]
        ];
    }
}
