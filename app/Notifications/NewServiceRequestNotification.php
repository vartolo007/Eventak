<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
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
        return ['database', FcmChannel::class]; // 💾 حفظ محلي في الداتابيز
    }

    /**
     * صياغة نص ديناميكي بناءً على نوع الطلب لتقديمه في حقل data بالجدول
     */
    public function toArray($notifiable)
    {
        $vendorName = $this->service->vendor->name ?? __('messages.auth.user_mismatch');

        switch ($this->requestType) {
            case 'create':
                $title = __('messages.notifications.service_request_create_title');
                $message = __('messages.notifications.service_request_create_message', [
                    'vendor_name' => $vendorName,
                    'service_name' => $this->service->name,
                ]);
                break;
            case 'update':
                $title = __('messages.notifications.service_request_update_title');
                $message = __('messages.notifications.service_request_update_message', [
                    'vendor_name' => $vendorName,
                    'service_name' => $this->service->name,
                ]);
                break;
            case 'delete':
                $title = __('messages.notifications.service_request_delete_title');
                $message = __('messages.notifications.service_request_delete_message', [
                    'vendor_name' => $vendorName,
                    'service_name' => $this->service->name,
                ]);
                break;
            default:
                $title = __('messages.notifications.service_request_default_title');
                $message = __('messages.notifications.service_request_default_message');
        }

        $data = [
            'service_id' => $this->service->id,
            'type' => 'service_request_' . $this->requestType,
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
        $vendorName = $this->service->vendor->name ?? __('messages.auth.user_mismatch');
        $title = '';
        $message = '';

        switch ($this->requestType) {
            case 'create':
                $title = __('messages.notifications.service_request_create_title');
                $message = __('messages.notifications.service_request_create_message', [
                    'vendor_name' => $vendorName,
                    'service_name' => $this->service->name,
                ]);
                break;
            case 'update':
                $title = __('messages.notifications.service_request_update_title');
                $message = __('messages.notifications.service_request_update_message', [
                    'vendor_name' => $vendorName,
                    'service_name' => $this->service->name,
                ]);
                break;
            case 'delete':
                $title = __('messages.notifications.service_request_delete_title');
                $message = __('messages.notifications.service_request_delete_message', [
                    'vendor_name' => $vendorName,
                    'service_name' => $this->service->name,
                ]);
                break;
            default:
                $title = __('messages.notifications.service_request_default_title');
                $message = __('messages.notifications.service_request_default_message');
        }

        return [
            'title' => $title,
            'message' => $message,
            'data' => ['type' => 'service_request_' . $this->requestType, 'service_id' => $this->service->id]
        ];
    }
}
