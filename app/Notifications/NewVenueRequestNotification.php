<?php

namespace App\Notifications;
use App\Channels\FcmChannel; // Add this line

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Venue;

class NewVenueRequestNotification extends Notification
{
    use Queueable;

    protected $venueRequest;
    protected $requestType; // 'create' أو 'update' أو 'delete'

    public function __construct(\App\Models\VenueRequest $venueRequest, $requestType)
    {
        $this->venueRequest = $venueRequest;
        $this->requestType = $requestType;
    }

    public function via($notifiable)
    {
        return ['database', FcmChannel::class]; // 💾 حفظ محلي في الداتابيز
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

        $data = [
            'venue_request_id' => $this->venueRequest->id,
            'type' => 'venue_request_' . $this->requestType,
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
        $ownerName = $this->venueRequest->owner->name ?? 'صاحب صالة';
        $title = '';
        $message = '';

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

        return [
            'title' => $title,
            'message' => $message,
            'data' => ['type' => 'venue_request_' . $this->requestType, 'venue_request_id' => $this->venueRequest->id]
        ];
    }
}
