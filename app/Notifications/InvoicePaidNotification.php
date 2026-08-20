<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Event;
use App\Models\Payment;

class InvoicePaidNotification extends Notification
{
    use Queueable;

    protected $event;
    protected $payment;

    public function __construct(Event $event, Payment $payment)
    {
        $this->event = $event;
        $this->payment = $payment;
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
            'payment_id' => $this->payment->id,
            'type' => 'invoice_paid',
            'title' => __('messages.notifications.invoice_paid_title', [], $locale),
            'message' => __('messages.notifications.invoice_paid_message', [
                'event_name' => $this->event->getTranslation('event_name', $locale),
                'amount' => $this->payment->amount,
            ], $locale),
        ];
    }

    // Firebase معلّق - toFcm
    // public function toFcm(object $notifiable): array
    // {
    //     $locale = $notifiable->locale ?? config('app.locale');
    //     return [
    //         'title' => __('messages.notifications.invoice_paid_title', [], $locale),
    //         'message' => __('messages.notifications.invoice_paid_message', [
    //             'event_name' => $this->event->getTranslation('event_name', $locale),
    //             'amount' => $this->payment->amount,
    //         ], $locale),
    //         'data' => [
    //             'type' => 'invoice_paid',
    //             'event_id' => $this->event->id,
    //             'payment_id' => $this->payment->id,
    //         ]
    //     ];
    // }
}
