<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\OtpMail; // سنستخدم نفس قالب الإيميل الأنيق
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ResetPasswordController extends Controller
{
    /**
     * المسار الأول: إرسال رمز الـ OTP للإيميل
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::query()->where('email', $request->email)->first();

        // توليد رمز وحفظه مع وقت انتهاء (10 دقائق)
        $otpCode = rand(100000, 999999);
        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // إرسال الإيميل
        Mail::to($user->email)->send(new OtpMail($otpCode));

        return response()->json([
            'message' => __('messages.auth.password_reset_email_sent')
        ]);
    }

    /**
     * المسار الثاني: التحقق من الرمز وتغيير كلمة المرور
     */
    public function resetPassword(Request $request)
    {
        // نطلب الإيميل، الرمز، وكلمة المرور الجديدة مع تأكيدها
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::query()->where('email', $request->email)->first();

        // التحقق من صحة الرمز
        if ($user->otp_code !== $request->otp_code) {
            return response()->json(['message' => __('messages.auth.otp_invalid')], 401);
        }

        // التحقق من صلاحية الوقت
        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => __('messages.auth.otp_expired')], 401);
        }

        // تحديث كلمة المرور وتشفيرها، وتصفير حقول الرمز للحماية
        $user->update([
            'password' => Hash::make($request->password),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'message' => __('messages.auth.password_changed')
        ]);
    }
}
