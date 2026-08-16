<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
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
        return ['database', FcmChannel::class];
    }

    public function toArray($notifiable)
    {
        $name = $this->venueRequest->name ?? 'صالتك';
        $type = $this->venueRequest->type; // create | update | delete

        if ($this->result === 'approved') {
            switch ($type) {
                case 'create':
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
                    break;
                case 'update':
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
                    break;
                case 'delete':
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
                    break;
                default:
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
            }
        } else {
            $reason = $this->venueRequest->admin_notes ? " " . $this->venueRequest->admin_notes : '';
            switch ($type) {
                case 'create':
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
                    break;
                case 'update':
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
                    break;
                case 'delete':
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
                    break;
                default:
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
            }
        }

        $data = [
            'venue_request_id' => $this->venueRequest->id,
            'type' => 'venue_result_' . $this->result,
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
        $name = $this->venueRequest->name ?? 'صالتك';
        $type = $this->venueRequest->type; // create | update | delete
        $title = '';
        $message = '';

        if ($this->result === 'approved') {
            switch ($type) {
                case 'create':
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
                    break;
                case 'update':
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
                    break;
                case 'delete':
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
                    break;
                default:
                    $title = __('messages.notifications.venue_result_approved_title');
                    $message = __('messages.notifications.venue_result_approved_message', ['venue_name' => $name]);
            }
        } else {
            $reason = $this->venueRequest->admin_notes ? " " . $this->venueRequest->admin_notes : '';
            switch ($type) {
                case 'create':
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
                    break;
                case 'update':
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
                    break;
                case 'delete':
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
                    break;
                default:
                    $title = __('messages.notifications.venue_result_rejected_title');
                    $message = __('messages.notifications.venue_result_rejected_message', ['venue_name' => $name]) . $reason;
            }
        }

        return [
            'title' => $title,
            'message' => $message,
            'data' => ['type' => 'venue_result_' . $this->result, 'venue_request_id' => $this->venueRequest->id]
        ];
    }
}
