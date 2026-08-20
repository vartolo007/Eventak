<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
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
        return ['database']; // Firebase معلّق
    }

    public function toArray($notifiable)
    {
        $locale = $notifiable->locale ?? config('app.locale');
        $payload = $this->buildPayload($locale);
        return [
            'service_id' => $this->service->id,
            'type' => 'service_result_' . $this->result,
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
    //         'data' => ['type' => 'service_result_' . $this->result, 'service_id' => $this->service->id]
    //     ];
    // }

    private function buildPayload(string $locale): array
    {
        $name = $this->service->getTranslation('name', $locale, false)
            ?: __('messages.service.default_name', [], $locale);

        switch ($this->result) {
            case 'approved':
                $title = __('messages.notifications.service_result_approved_title', [], $locale);
                $message = __('messages.notifications.service_result_approved_message', ['service_name' => $name], $locale);
                break;
            case 'rejected':
                $title = __('messages.notifications.service_result_rejected_title', [], $locale);
                $message = __('messages.notifications.service_result_rejected_message', ['service_name' => $name], $locale);
                break;
            case 'delete_approved':
                $title = __('messages.notifications.service_result_delete_approved_title', [], $locale);
                $message = __('messages.notifications.service_result_delete_approved_message', ['service_name' => $name], $locale);
                break;
            case 'delete_rejected':
                $title = __('messages.notifications.service_result_delete_rejected_title', [], $locale);
                $message = __('messages.notifications.service_result_delete_rejected_message', ['service_name' => $name], $locale);
                break;
            default:
                $title = __('messages.notifications.service_result_default_title', [], $locale);
                $message = __('messages.notifications.service_result_default_message', ['service_name' => $name], $locale);
        }

        return compact('title', 'message');
    }
}
