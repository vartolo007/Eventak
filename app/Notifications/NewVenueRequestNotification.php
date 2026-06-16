<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Venue;

class NewVenueRequestNotification extends Notification
{
    use Queueable;

    protected $venue;
    protected $requestType; // 'create' أو 'update' أو 'delete'

    public function __construct(Venue $venue, $requestType)
    {
        $this->venue = $venue;
        $this->requestType = $requestType;
    }

    public function via($notifiable)
    {
        return ['database']; // 💾 حفظ محلي في الداتابيز
    }

    public function toArray($notifiable)
    {
        $ownerName = $this->venue->owner->name ?? 'صاحب صالة';

        switch ($this->requestType) {
            case 'create':
                $title = 'طلب إضافة صالة جديدة';
                $message = "قام صاحب الصالة {$ownerName} بتقديم طلب إضافة صالة جديدة باسم ({$this->venue->name}) وبانتظار مراجعتك.";
                break;
            case 'update':
                $title = 'طلب تعديل بيانات صالة';
                $message = "قام صاحب الصالة {$ownerName} بتعديل بيانات الصالة ({$this->venue->name}) وبانتظار موافقتك على التعديلات.";
                break;
            case 'delete':
                $title = 'طلب حذف صالة حرج! ⚠️';
                $message = "يرغب صاحب الصالة {$ownerName} بحذف صالته ({$this->venue->name}) نهائياً من النظام.";
                break;
            default:
                $title = 'طلب جديد بخصوص الصالات';
                $message = 'هناك تحديث جديد بحاجة لمراجعتك.';
        }

        return [
            'venue_id' => $this->venue->id,
            'type' => 'venue_request_' . $this->requestType,
            'title' => $title,
            'message' => $message,
        ];
    }
}
