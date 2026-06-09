<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // عرض كافة إشعارات المستخدم الحالي (مقروءة وغير مقروءة)
    public function index(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->notifications
        ]);
    }

    // جلب الإشعارات غير المقروءة فقط
    public function unread(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->unreadNotifications
        ]);
    }

    // تحويل إشعار معين إلى مقروء
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json([
                'status' => 'success',
                'message' => 'تم تعيين الإشعار كمقروء.'
            ]);
        }

        return response()->json(['message' => 'الإشعار غير موجود.'], 404);
    }
}
