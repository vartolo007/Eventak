<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEventNotification extends Notification
{
    use Queueable;

    protected $event;

    public function __construct($event) {
        $this->event = $event;
    }

    public function via($notifiable): array {
        return ['database']; // حفظ الإشعار في قاعدة البيانات ليقرأه الفرونت إند
    }

    public function toArray($notifiable): array {
        return [
            'event_id' => $this->event->id, // معرف المناسبة
            'title' => 'طلب مناسبة جديد', // عنوان الإشعار
            'message' => "لديك طلب مناسبة جديد ({$this->event->event_name}) من الزبون {$this->event->customer->name} بانتظار مراجعتك.", // رسالة الإشعار
            'type' => 'new_event_request' // نوع الإشعار لتسهيل التعامل معه في الواجهة الأمامية
        ];
    }
}
