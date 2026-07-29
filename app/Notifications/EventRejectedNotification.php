<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
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
        return ['database', FcmChannel::class]; // يمكنك إضافة 'mail' أو 'broadcast' حسب الحاجة
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if ($this->notificationType === 'venue_available') {
            $data = [
                'type' => 'venue_available',
                'event_id' => $this->event->id,
                'event_name' => $this->event->event_name,
                'venue_name' => $this->event->venue->name ?? 'الصالة',
                'date' => $this->event->date,
                'message' => $this->customMessage ?? 'أهلاً بك! الصالة ' . ($this->event->venue->name ?? '') . ' أصبحت متاحة الآن في تاريخ ' . $this->event->date . ' بسبب إلغاء حجز سابق. يمكنك مراجعة طلبك أو تقديم طلب جديد.'
            ];

            return $data;
        }

        return [
            'type' => 'event_rejected',
            'event_id' => $this->event->id,
            'event_name' => $this->event->event_name,
            'message' => $this->customMessage ?? "عذراً، تم رفض طلب حجز مناسبتك ({$this->event->event_name}) بتاريخ {$this->event->date}.",
        ];
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        if ($this->notificationType === 'venue_available') {
            return [
                'title' => 'صالة أصبحت متاحة!',
                'message' => $this->customMessage ?? 'أهلاً بك! الصالة ' . ($this->event->venue->name ?? '') . ' أصبحت متاحة الآن في تاريخ ' . $this->event->date . ' بسبب إلغاء حجز سابق. يمكنك مراجعة طلبك أو تقديم طلب جديد.',
                'data' => [
                    'type' => 'venue_available',
                    'event_id' => $this->event->id,
                    'event_name' => $this->event->event_name,
                    'venue_name' => $this->event->venue->name ?? 'الصالة',
                    'date' => $this->event->date,
                ]
            ];
        }

        return [
            'title' => 'تم رفض طلب الحجز',
            'message' => $this->customMessage ?? "عذراً، تم رفض طلب حجز مناسبتك ({$this->event->event_name}) بتاريخ {$this->event->date}.",
            'data' => ['type' => 'event_rejected', 'event_id' => $this->event->id]
        ];
    }
}
