<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class NotificationController extends Controller
{
    protected $messaging;

    // حقن خدمة Firebase Messaging
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    // ==========================================
    // 1. إدارات الـ FCM Tokens
    // ==========================================

    /**
     * حفظ أو تحديث الـ FCM Token الخاص بالمستخدم
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تحديث رمز FCM بنجاح.',
        ]);
    }

    // ==========================================
    // 2. إرسال إشعار FCM فوري (Push Notification)
    // ==========================================

    /**
     * إرسال إشعار فوري لمستخدم معين أو للهاتف الحالي
     */
    public function sendFcmNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body'  => 'required|string',
            'fcm_token' => 'nullable|string', // اختياري: إذا لم يرسل يتم الأخذ من المستخدم الحالي
        ]);

        // جلب الـ Token إما من الممرر بالطلب أو من قاعدة البيانات للمستخدم الحالي
        $token = $request->fcm_token ?? $request->user()->fcm_token;

        if (!$token) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لا يوجد رمز FCM مسجل لهذا المستخدم.',
            ], 400);
        }

        try {
            $notification = FcmNotification::create($request->title, $request->body);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($request->input('data', [])); // بيانات إضافية (اختياري)

            $this->messaging->send($message);

            return response()->json([
                'status'  => 'success',
                'message' => 'تم إرسال الإشعار بنجاح!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'فشل إرسال الإشعار: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==========================================
    // 3. إشعارات قاعدة البيانات (الكود الخاص بك)
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
                'message' => 'تم تعيين الإشعار كمقروء.'
            ]);
        }

        return response()->json(['message' => 'الإشعار غير موجود.'], 404);
    }
}
