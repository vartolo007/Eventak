<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Venue;

class NewVenueRequestNotification extends Notification // تم تغيير اسم الكلاس ليتناسب مع الكائن المُمرر
{
    use Queueable;

    protected $venueRequest; // تم تغيير اسم المتغير ليعكس الكائن المُمرر
    protected $requestType; // 'create' أو 'update' أو 'delete'

    public function __construct(\App\Models\VenueRequest $venueRequest, $requestType) // تم تغيير نوع الكائن المتوقع
    {
        $this->venueRequest = $venueRequest;
        $this->requestType = $requestType;
    }

    public function via($notifiable)
    {
        return ['database']; // 💾 حفظ محلي في الداتابيز
    }

    public function toArray($notifiable)
    {
        $ownerName = $this->venueRequest->owner->name ?? 'صاحب صالة';

        switch ($this->requestType) {
            case 'create':
                $title = 'طلب إضافة صالة جديدة';
                $message = "قام صاحب الصالة {$ownerName} بتقديم طلب إضافة صالة جديدة باسم ({$this->venueRequest->name}) وبانتظار مراجعتك.";
                break;
            case 'update':
                $title = 'طلب تعديل بيانات صالة';
                $message = "قام صاحب الصالة {$ownerName} بتعديل بيانات الصالة ({$this->venueRequest->name}) وبانتظار موافقتك على التعديلات.";
                break;
            case 'delete':
                $title = 'طلب حذف صالة حرج! ⚠️';
                $message = "يرغب صاحب الصالة {$ownerName} بحذف صالته ({$this->venueRequest->name}) نهائياً من النظام.";
                break;
            default:
                $title = 'طلب جديد بخصوص الصالات';
                $message = 'هناك تحديث جديد بحاجة لمراجعتك.';
        }

        return [ // تم تغيير venue_id إلى venue_request_id
            'venue_request_id' => $this->venueRequest->id,
            'type' => 'venue_request_' . $this->requestType,
            'title' => $title,
            'message' => $message,
        ];
    }
}
