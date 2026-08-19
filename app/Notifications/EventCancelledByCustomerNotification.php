<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Event;

class EventCancelledByCustomerNotification extends Notification
{
    use Queueable;

    protected $event;
    protected $refundAmount;

    public function __construct(Event $event, $refundAmount = null)
    {
        $this->event = $event;
        $this->refundAmount = $refundAmount;
    }

    public function via($notifiable): array
    {
        return ['database', FcmChannel::class]; // 💾 حفظ في قاعدة البيانات لصاحب الصالة والموردين
    }

    public function toArray($notifiable): array
    {
        $locale = $notifiable->locale ?? config('app.locale');
        return $this->buildPayload($locale);
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        $locale = $notifiable->locale ?? config('app.locale');
        $payload = $this->buildPayload($locale);
        return [
            'title' => $payload['title'],
            'message' => $payload['message'],
            'data' => ['type' => 'booking_cancelled_by_customer', 'event_id' => $this->event->id]
        ];
    }

    private function buildPayload(string $locale): array
    {
        $message = __('messages.notifications.booking_cancelled_by_customer_message', [
            'event_name' => $this->event->getTranslation('event_name', $locale),
            'date' => $this->event->date,
        ], $locale);

        if (!is_null($this->refundAmount)) {
            $message .= $this->refundAmount > 0
                ? ' ' . __('messages.event.cancel_success_refund', [
                    'amount' => $this->refundAmount,
                    'percent' => 100,
                ], $locale)
                : ' ' . __('messages.event.cancel_success_no_refund', [], $locale);
        }

        return [
            'event_id' => $this->event->id,
            'type' => 'booking_cancelled_by_customer',
            'title' => __('messages.notifications.booking_cancelled_by_customer_title', [], $locale),
            'message' => $message,
        ];
    }
}
