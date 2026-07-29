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
                $title = 'تمت الموافقة على خدمتك ✅';
                $message = "تمت الموافقة على الخدمة ({$name}) وأصبحت الآن نشطة ومعروضة في التطبيق.";
                break;
            case 'rejected':
                $title = 'تم رفض خدمتك ❌';
                $message = "تم رفض الخدمة ({$name}) وتحويلها إلى غير نشطة. يمكنك تعديلها وإعادة إرسالها.";
                break;
            case 'delete_approved':
                $title = 'تمت الموافقة على حذف الخدمة 🗑️';
                $message = "تمت الموافقة على طلب حذف الخدمة ({$name}) وتمت إزالتها نهائياً من النظام.";
                break;
            case 'delete_rejected':
                $title = 'تم رفض طلب حذف الخدمة';
                $message = "تم رفض طلب حذف الخدمة ({$name})، وأعيدت نشطة ومتوفرة في التطبيق.";
                break;
            default:
                $title = 'تحديث بخصوص خدمتك';
                $message = "هناك تحديث جديد بخصوص الخدمة ({$name}).";
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
                $title = 'تمت الموافقة على خدمتك ✅';
                $message = "تمت الموافقة على الخدمة ({$name}) وأصبحت الآن نشطة ومعروضة في التطبيق.";
                break;
            case 'rejected':
                $title = 'تم رفض خدمتك ❌';
                $message = "تم رفض الخدمة ({$name}) وتحويلها إلى غير نشطة. يمكنك تعديلها وإعادة إرسالها.";
                break;
            case 'delete_approved':
                $title = 'تمت الموافقة على حذف الخدمة 🗑️';
                $message = "تمت الموافقة على طلب حذف الخدمة ({$name}) وتمت إزالتها نهائياً من النظام.";
                break;
            case 'delete_rejected':
                $title = 'تم رفض طلب حذف الخدمة';
                $message = "تم رفض طلب حذف الخدمة ({$name})، وأعيدت نشطة ومتوفرة في التطبيق.";
                break;
            default:
                $title = 'تحديث بخصوص خدمتك';
                $message = "هناك تحديث جديد بخصوص الخدمة ({$name}).";
        }

        return [
            'title' => $title,
            'message' => $message,
            'data' => ['type' => 'service_result_' . $this->result, 'service_id' => $this->service->id]
        ];
    }
}
