<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
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
        return ['database', FcmChannel::class]; // 💾 حفظ محلي في الداتابيز
    }

    public function toArray($notifiable)
    {
        $ownerName = $this->venueRequest->owner->name ?? __('messages.auth.user_mismatch');

        switch ($this->requestType) {
            case 'create':
                $title = __('messages.notifications.venue_request_create_title');
                $message = __('messages.notifications.venue_request_create_message', [
                    'owner_name' => $ownerName,
                    'venue_name' => $this->venueRequest->name,
                ]);
                break;
            case 'update':
                $title = __('messages.notifications.venue_request_update_title');
                $message = __('messages.notifications.venue_request_update_message', [
                    'owner_name' => $ownerName,
                    'venue_name' => $this->venueRequest->name,
                ]);
                break;
            case 'delete':
                $title = __('messages.notifications.venue_request_delete_title');
                $message = __('messages.notifications.venue_request_delete_message', [
                    'owner_name' => $ownerName,
                    'venue_name' => $this->venueRequest->name,
                ]);
                break;
            default:
                $title = __('messages.notifications.venue_request_default_title');
                $message = __('messages.notifications.venue_request_default_message');
        }

        $data = [
            'venue_request_id' => $this->venueRequest->id,
            'type' => 'venue_request_' . $this->requestType,
            'title' => $title,
            'message' => $message,
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
        $ownerName = $this->venueRequest->owner->name ?? __('messages.auth.user_mismatch');
        $title = '';
        $message = '';

        switch ($this->requestType) {
            case 'create':
                $title = __('messages.notifications.venue_request_create_title');
                $message = __('messages.notifications.venue_request_create_message', [
                    'owner_name' => $ownerName,
                    'venue_name' => $this->venueRequest->name,
                ]);
                break;
            case 'update':
                $title = __('messages.notifications.venue_request_update_title');
                $message = __('messages.notifications.venue_request_update_message', [
                    'owner_name' => $ownerName,
                    'venue_name' => $this->venueRequest->name,
                ]);
                break;
            case 'delete':
                $title = __('messages.notifications.venue_request_delete_title');
                $message = __('messages.notifications.venue_request_delete_message', [
                    'owner_name' => $ownerName,
                    'venue_name' => $this->venueRequest->name,
                ]);
                break;
            default:
                $title = __('messages.notifications.venue_request_default_title');
                $message = __('messages.notifications.venue_request_default_message');
        }

        return [
            'title' => $title,
            'message' => $message,
            'data' => ['type' => 'venue_request_' . $this->requestType, 'venue_request_id' => $this->venueRequest->id]
        ];
    }
}
