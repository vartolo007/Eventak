<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Venue;

class NewVenueRequestNotification extends Notification
{
    use Queueable;

    protected $venueRequest;
    protected $requestType; // 'create' أو 'update' أو 'delete'

    public function __construct(\App\Models\VenueRequest $venueRequest, $requestType)
    {
        $this->venueRequest = $venueRequest;
        $this->requestType = $requestType;
    }

    public function via($notifiable)
    {
        return ['database']; // Firebase معلّق - كان: ['database', FcmChannel::class]
    }

    public function toArray($notifiable)
    {
        $locale = $notifiable->locale ?? config('app.locale');
        $payload = $this->buildPayload($locale);
        return [
            'venue_request_id' => $this->venueRequest->id,
            'type' => 'venue_request_' . $this->requestType,
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
    //         'data' => ['type' => 'venue_request_' . $this->requestType, 'venue_request_id' => $this->venueRequest->id]
    //     ];
    // }

    private function buildPayload(string $locale): array
    {
        $ownerName = $this->venueRequest->owner->name ?? __('messages.auth.user_mismatch', [], $locale);
        $venueName = $this->venueRequest->getTranslation('name', $locale);

        switch ($this->requestType) {
            case 'create':
                $title = __('messages.notifications.venue_request_create_title', [], $locale);
                $message = __('messages.notifications.venue_request_create_message', [
                    'owner_name' => $ownerName, 'venue_name' => $venueName,
                ], $locale);
                break;
            case 'update':
                $title = __('messages.notifications.venue_request_update_title', [], $locale);
                $message = __('messages.notifications.venue_request_update_message', [
                    'owner_name' => $ownerName, 'venue_name' => $venueName,
                ], $locale);
                break;
            case 'delete':
                $title = __('messages.notifications.venue_request_delete_title', [], $locale);
                $message = __('messages.notifications.venue_request_delete_message', [
                    'owner_name' => $ownerName, 'venue_name' => $venueName,
                ], $locale);
                break;
            default:
                $title = __('messages.notifications.venue_request_default_title', [], $locale);
                $message = __('messages.notifications.venue_request_default_message', [], $locale);
        }

        return compact('title', 'message');
    }
}
