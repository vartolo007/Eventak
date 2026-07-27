<?php

namespace App\Notifications;

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
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $name = $this->venueRequest->name ?? 'صالتك';
        $type = $this->venueRequest->type; // create | update | delete

        if ($this->result === 'approved') {
            switch ($type) {
                case 'create':
                    $title = 'تمت الموافقة على إضافة صالتك ✅';
                    $message = "تمت الموافقة على إضافة الصالة ({$name}) وأصبحت الآن نشطة ومتاحة للحجز.";
                    break;
                case 'update':
                    $title = 'تمت الموافقة على تعديل صالتك ✅';
                    $message = "تمت الموافقة على تعديلات الصالة ({$name}) وتم تحديث بياناتها الحية.";
                    break;
                case 'delete':
                    $title = 'تمت الموافقة على حذف الصالة 🗑️';
                    $message = "تمت الموافقة على حذف الصالة ({$name}) وتمت إزالتها من النظام.";
                    break;
                default:
                    $title = 'تمت الموافقة على طلبك ✅';
                    $message = "تمت الموافقة على طلبك بخصوص الصالة ({$name}).";
            }
        } else {
            $reason = $this->venueRequest->admin_notes ? " السبب: {$this->venueRequest->admin_notes}" : '';
            switch ($type) {
                case 'create':
                    $title = 'تم رفض إضافة صالتك ❌';
                    $message = "تم رفض طلب إضافة الصالة ({$name}).{$reason}";
                    break;
                case 'update':
                    $title = 'تم رفض تعديل صالتك ❌';
                    $message = "تم رفض تعديلات الصالة ({$name})، وبقيت البيانات الأصلية دون تغيير.{$reason}";
                    break;
                case 'delete':
                    $title = 'تم رفض حذف الصالة';
                    $message = "تم رفض طلب حذف الصالة ({$name})، وبقيت نشطة ومتاحة للحجز.{$reason}";
                    break;
                default:
                    $title = 'تم رفض طلبك ❌';
                    $message = "تم رفض طلبك بخصوص الصالة ({$name}).{$reason}";
            }
        }

        return [
            'venue_request_id' => $this->venueRequest->id,
            'type' => 'venue_result_' . $this->result,
            'title' => $title,
            'message' => $message,
        ];
    }
}
