<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\VenueRequest;

class VenueRequestResultNotification extends Notification
{
    use Queueable;

    protected $venueRequest;
    protected $result; // 'approved' | 'rejected'

    public function __construct(VenueRequest $venueRequest, $result)
    {
        $this->venueRequest = $venueRequest;
        $this->result = $result;
    }

    public function via($notifiable)
    {
        return ['database']; // Firebase معلّق
    }

    public function toArray($notifiable)
    {
        $locale = $notifiable->locale ?? config('app.locale');
        $payload = $this->buildPayload($locale);
        return [
            'venue_request_id' => $this->venueRequest->id,
            'type' => 'venue_result_' . $this->result,
            'title' => $payload['title'],
            'message' => $payload['message'],
        ];
    }

    // Firebase معلّق - toFcm
    // public function toFcm(object $notifiable): array
    // {
    //     $locale = $notifiable->locale ?? config('app.locale');
    //     $payload = $this->buildPayload($locale);
    //     return [
    //         'title' => $payload['title'],
    //         'message' => $payload['message'],
    //         'data' => ['type' => 'venue_result_' . $this->result, 'venue_request_id' => $this->venueRequest->id]
    //     ];
    // }

    private function buildPayload(string $locale): array
    {
        $name = $this->venueRequest->getTranslation('name', $locale, false)
            ?: __('messages.venue.default_name', [], $locale);

        if ($this->result === 'approved') {
            $title = __('messages.notifications.venue_result_approved_title', [], $locale);
            $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name], $locale);
        } else {
            $reason = $this->venueRequest->admin_notes ? " " . $this->venueRequest->admin_notes : '';
            $title = __('messages.notifications.venue_result_rejected_title', [], $locale);
            $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name], $locale) . $reason;
        }

        return compact('title', 'message');
    }
}
