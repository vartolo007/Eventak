<?php

namespace App\Notifications;

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
        return ['database']; // 💾 حفظ في قاعدة البيانات للزبون
    }

    public function toArray($notifiable)
    {
        return [
            'event_id' => $this->event->id,
            'service_id' => $this->service->id,
            'type' => 'vendor_service_approved',
            'title' => 'تحديث بشأن الخدمات 🚚',
            'message' => "وافق المورد على تقديم خدمة ({$this->service->name}) لمناسبتك الحاملة لاسم ({$this->event->event_name}).",
        ];
    }
}
