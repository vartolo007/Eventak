<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Firebase معلّق
// use Kreait\Firebase\Contract\Messaging;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Firebase\Messaging\Notification as FcmNotification;

class NotificationController extends Controller
{
    // Firebase معلّق
    // protected $messaging;
    // public function __construct(Messaging $messaging)
    // {
    //     $this->messaging = $messaging;
    // }

    // ==========================================
    // Firebase معلّق - إدارات الـ FCM Tokens
    // ==========================================

    // public function updateFcmToken(Request $request)
    // {
    //     $request->validate([
    //         'fcm_token' => 'required|string',
    //     ]);
    //     $request->user()->update([
    //         'fcm_token' => $request->fcm_token,
    //     ]);
    //     return response()->json([
    //         'status'  => 'success',
    //         'message' => __('messages.notification.fcm_token_updated'),
    //         'data'    => null,
    //     ]);
    // }

    // ==========================================
    // Firebase معلّق - إرسال إشعار FCM فوري
    // ==========================================

    // public function sendFcmNotification(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string',
    //         'body'  => 'required|string',
    //         'fcm_token' => 'nullable|string',
    //     ]);
    //     $token = $request->fcm_token ?? $request->user()->fcm_token;
    //     if (!$token) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => __('messages.notification.fcm_token_missing'),
    //         ], 400);
    //     }
    //     try {
    //         $notification = FcmNotification::create($request->title, $request->body);
    //         $message = CloudMessage::withTarget('token', $token)
    //             ->withNotification($notification)
    //             ->withData($request->input('data', []));
    //         $this->messaging->send($message);
    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => __('messages.notification.fcm_sent_success'),
    //             'data'    => null,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => __('messages.notification.fcm_send_failed', ['error' => $e->getMessage()]),
    //         ], 500);
    //     }
    // }

    // ==========================================
    // إشعارات قاعدة البيانات (Laravel Notifications)
    // ==========================================

    // عرض كافة إشعارات المستخدم الحالي (مقروءة وغير مقروءة)
    public function index(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user()->notifications
        ]);
    }

    // جلب الإشعارات غير المقروءة فقط
    public function unread(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user()->unreadNotifications
        ]);
    }

    // تحويل إشعار معين إلى مقروء
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json([
                'status'  => 'success',
                'message' => __('messages.notification.marked_read'),
                'data'    => $notification,
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => __('messages.notification.not_found'),
            'data'    => null,
        ], 404);
    }
}
