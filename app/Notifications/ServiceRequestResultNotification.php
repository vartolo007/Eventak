<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Service;

class ServiceRequestResultNotification extends Notification
{
    use Queueable;

    protected $service;
    protected $result; // 'approved' | 'rejected' | 'delete_approved' | 'delete_rejected'

    public function __construct(Service $service, $result)
    {
        $this->service = $service;
        $this->result = $result;
    }

    public function via($notifiable)
    {
        return ['database', FcmChannel::class];
    }

    public function toArray($notifiable)
    {
        $name = $this->service->name ?? 'خدمتك';

        switch ($this->result) {
            case 'approved':
                $title = __('messages.notifications.service_result_approved_title');
                $message = __('messages.notifications.service_result_approved_message', ['service_name' => $name]);
                break;
            case 'rejected':
                $title = __('messages.notifications.service_result_rejected_title');
                $message = __('messages.notifications.service_result_rejected_message', ['service_name' => $name]);
                break;
            case 'delete_approved':
                $title = __('messages.notifications.service_result_delete_approved_title');
                $message = __('messages.notifications.service_result_delete_approved_message', ['service_name' => $name]);
                break;
            case 'delete_rejected':
                $title = __('messages.notifications.service_result_delete_rejected_title');
                $message = __('messages.notifications.service_result_delete_rejected_message', ['service_name' => $name]);
                break;
            default:
                $title = __('messages.notifications.service_result_default_title');
                $message = __('messages.notifications.service_result_default_message', ['service_name' => $name]);
        }

        $data = [
            'service_id' => $this->service->id,
            'type' => 'service_result_' . $this->result,
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
        $name = $this->service->name ?? 'خدمتك';
        $title = '';
        $message = '';

        switch ($this->result) {
            case 'approved':
                $title = __('messages.notifications.service_result_approved_title');
                $message = __('messages.notifications.service_result_approved_message', ['service_name' => $name]);
                break;
            case 'rejected':
                $title = __('messages.notifications.service_result_rejected_title');
                $message = __('messages.notifications.service_result_rejected_message', ['service_name' => $name]);
                break;
            case 'delete_approved':
                $title = __('messages.notifications.service_result_delete_approved_title');
                $message = __('messages.notifications.service_result_delete_approved_message', ['service_name' => $name]);
                break;
            case 'delete_rejected':
                $title = __('messages.notifications.service_result_delete_rejected_title');
                $message = __('messages.notifications.service_result_delete_rejected_message', ['service_name' => $name]);
                break;
            default:
                $title = __('messages.notifications.service_result_default_title');
                $message = __('messages.notifications.service_result_default_message', ['service_name' => $name]);
        }

        return [
            'title' => $title,
            'message' => $message,
            'data' => ['type' => 'service_result_' . $this->result, 'service_id' => $this->service->id]
        ];
    }
}
