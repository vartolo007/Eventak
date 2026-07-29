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
        $vendorName = $this->service->vendor->name ?? 'مورد';

        switch ($this->requestType) {
            case 'create':
                $title = 'طلب إضافة خدمة جديدة';
                $message = "قام المورد {$vendorName} بإضافة خدمة جديدة باسم ({$this->service->name}) وهي بحاجة لمراجعتك وقبولها.";
                break;
            case 'update':
                $title = 'طلب تعديل خدمة';
                $message = "قام المورد {$vendorName} بتعديل بيانات الخدمة ({$this->service->name}) وبانتظار موافقتك على التعديلات.";
                break;
            case 'delete':
                $title = 'طلب حذف خدمة ⚠️';
                $message = "يرغب المورد {$vendorName} بحذف الخدمة ({$this->service->name}) بشكل نهائي من التطبيق.";
                break;
            default:
                $title = 'طلب مراجعة جديد';
                $message = 'هناك تحديث جديد على الخدمات بحاجة لمراجعتك.';
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
        $vendorName = $this->service->vendor->name ?? 'مورد';
        $title = '';
        $message = '';

        switch ($this->requestType) {
            case 'create':
                $title = 'طلب إضافة خدمة جديدة';
                $message = "قام المورد {$vendorName} بإضافة خدمة جديدة باسم ({$this->service->name}) وهي بحاجة لمراجعتك وقبولها.";
                break;
            case 'update':
                $title = 'طلب تعديل خدمة';
                $message = "قام المورد {$vendorName} بتعديل بيانات الخدمة ({$this->service->name}) وبانتظار موافقتك على التعديلات.";
                break;
            case 'delete':
                $title = 'طلب حذف خدمة ⚠️';
                $message = "يرغب المورد {$vendorName} بحذف الخدمة ({$this->service->name}) بشكل نهائي من التطبيق.";
                break;
            default:
                $title = 'طلب مراجعة جديد';
                $message = 'هناك تحديث جديد على الخدمات بحاجة لمراجعتك.';
        }

        return [
            'title' => $title,
            'message' => $message,
            'data' => ['type' => 'service_request_' . $this->requestType, 'service_id' => $this->service->id]
        ];
    }
}
