<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class OtpAuthController extends Controller
{
    /**
     * الخطوة الأولى: طلب الرمز وإرساله للإيميل
     */
    public function sendOtp(Request $request)
    {
        // 1. التحقق من أن الإيميل مرسل وموجود في النظام
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::query()->where('email', $request->email)->first();

        // 2. توليد رمز عشوائي من 6 أرقام
        $otpCode = rand(100000, 999999);

        // 3. حفظ الرمز ووقت الانتهاء (بعد 5 دقائق من الآن) في قاعدة البيانات
        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // 4. إرسال الإيميل باستخدام القالب الذي صممناه
        Mail::to($user->email)->send(new OtpMail($otpCode));

        return response()->json([
            'message' => 'تم إرسال رمز الدخول إلى بريدك الإلكتروني بنجاح.'
        ]);
    }

    /**
     * الخطوة الثانية: التحقق من الرمز وإصدار التوكن
     */
    public function verifyOtp(Request $request)
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = User::query()->where('email', $request->email)->first();

        // 2. التأكد من صحة الرمز وصلاحية الوقت
        if ($user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'الرمز غير صحيح.'], 401);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'الرمز منتهي الصلاحية، يرجى طلب رمز جديد.'], 401);
        }

        // 3. إذا كان كل شيء سليم، نقوم بتصفير حقول الرمز للأمان
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // 4. إصدار التوكن وتسجيل الدخول
        $token = $user->createToken('vendor_venue_token')->plainTextToken;

        return response()->json([
            'message' => ' تم التحقق بنجاح، أهلاً بك عزيزي ' . $user->name,
            'user' => $user,
            'token' => $token
        ]);
    }
}
