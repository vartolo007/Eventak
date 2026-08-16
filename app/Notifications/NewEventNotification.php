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
        return [
            'event_id' => $this->event->id,
            'title' => __('messages.notifications.new_event_request_title'),
            'message' => __('messages.notifications.new_event_request_message', [
                'event_name' => $this->event->event_name,
                'customer_name' => $this->event->customer->name,
            ]),
            'type' => 'new_event_request'
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('messages.notifications.new_event_request_title'),
            'message' => __('messages.notifications.new_event_request_message', [
                'event_name' => $this->event->event_name,
                'customer_name' => $this->event->customer->name,
            ]),
            'data' => ['type' => 'new_event_request', 'event_id' => $this->event->id]
        ];
    }
}
