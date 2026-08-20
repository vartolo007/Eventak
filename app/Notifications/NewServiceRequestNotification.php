<?php

namespace App\Notifications;
// use App\Channels\FcmChannel; // Firebase معلّق
// use App\Services\FirebaseService; // Firebase معلّق
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Service;

class NewServiceRequestNotification extends Notification
{
    use Queueable;

    protected $service;
    protected $requestType; // 'create' أو 'update' أو 'delete'

    /**
     * استقبال كائن الخدمة ونوع الطلب عند إطلاق الإشعار
     */
    public function __construct(Service $service, $requestType)
    {
        $this->service = $service;
        $this->requestType = $requestType;
    }

    /**
     * تحديد قناة التوصيل لتكون قاعدة البيانات فقط
     */
    public function via($notifiable)
    {
        return ['database']; // Firebase معلّق - كان: ['database', FcmChannel::class]
    }

    /**
     * صياغة نص ديناميكي بناءً على نوع الطلب لتقديمه في حقل data بالجدول
     */
    public function toArray($notifiable)
    {
        $locale = $notifiable->locale ?? config('app.locale');
        $payload = $this->buildPayload($locale);
        return [
            'service_id' => $this->service->id,
            'type' => 'service_request_' . $this->requestType,
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
    //         'data' => ['type' => 'service_request_' . $this->requestType, 'service_id' => $this->service->id]
    //     ];
    // }

    private function buildPayload(string $locale): array
    {
        $vendorName = $this->service->vendor->name ?? __('messages.auth.user_mismatch', [], $locale);
        $serviceName = $this->service->getTranslation('name', $locale);

        switch ($this->requestType) {
            case 'create':
                $title = __('messages.notifications.service_request_create_title', [], $locale);
                $message = __('messages.notifications.service_request_create_message', [
                    'vendor_name' => $vendorName, 'service_name' => $serviceName,
                ], $locale);
                break;
            case 'update':
                $title = __('messages.notifications.service_request_update_title', [], $locale);
                $message = __('messages.notifications.service_request_update_message', [
                    'vendor_name' => $vendorName, 'service_name' => $serviceName,
                ], $locale);
                break;
            case 'delete':
                $title = __('messages.notifications.service_request_delete_title', [], $locale);
                $message = __('messages.notifications.service_request_delete_message', [
                    'vendor_name' => $vendorName, 'service_name' => $serviceName,
                ], $locale);
                break;
            default:
                $title = __('messages.notifications.service_request_default_title', [], $locale);
                $message = __('messages.notifications.service_request_default_message', [], $locale);
        }

        return compact('title', 'message');
    }
}
