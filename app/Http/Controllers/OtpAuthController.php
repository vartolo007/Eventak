<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class OtpAuthController extends Controller
{
    /**
     * الخطوة الأولى: طلب الرمز وإرساله (إما عبر الإيميل أو الواتساب)
     */
    public function sendOtp(Request $request)
    {
        // 1. التحقق من المدخلات: يجب إرسال إما إيميل أو رقم هاتف
        $request->validate([
            'email' => 'required_without:phone|email|exists:users,email',
            'phone' => 'required_without:email|string|exists:users,phone',
        ]);

        // 2. البحث عن المستخدم (سواء بالإيميل أو رقم الهاتف)
        $user = User::query()
            ->when($request->email, fn($query) => $query->where('email', $request->email))
            ->when($request->phone, fn($query) => $query->where('phone', $request->phone))
            ->first();

        // 3. توليد رمز عشوائي من 6 أرقام وتحديد وقت الانتهاء
        $otpCode = rand(100000, 999999);

        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // 4. الإرسال حسب الوسيلة المتوفرة في الطلب (Request)
        if ($request->has('email')) {
            // -- إرسال عبر الإيميل --
            Mail::to($user->email)->send(new OtpMail($otpCode));

            return response()->json([
                'status'  => 'success',
                'message' => 'تم إرسال رمز الدخول إلى بريدك الإلكتروني بنجاح.'
            ]);
        } elseif ($request->has('phone')) {
            // -- إرسال عبر الواتساب --
            try {
                $response = Http::post('http://127.0.0.1:3000/send-otp', [
                    'phone'   => $user->phone,
                    'message' => "مرحباً بك في منصة إيفينتك 🎫\nرمز التحقق الخاص بك هو: *{$otpCode}*\nينتهي بعد 5 دقائق."
                ]);

                if ($response->successful()) {
                    return response()->json([
                        'status'  => 'success',
                        'message' => 'تم إرسال رمز التحقق إلى الواتساب بنجاح.'
                    ]);
                } else {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'فشل سيرفر الواتساب المحلي في معالجة الإرسال.'
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'تعذر الاتصال ببوابة الواتساب، تأكد من تشغيل سيرفر Node.js.'
                ], 500);
            }
        }
    }

    /**
     * الخطوة الثانية: التحقق من الرمز وإصدار التوكن
     */
    public function verifyOtp(Request $request)
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'email'    => 'required_without:phone|email|exists:users,email',
            'phone'    => 'required_without:email|string|exists:users,phone',
            'otp_code' => 'required|numeric',
        ]);

        // 2. البحث عن المستخدم
        $user = User::query()
            ->when($request->email, fn($query) => $query->where('email', $request->email))
            ->when($request->phone, fn($query) => $query->where('phone', $request->phone))
            ->first();

        // 3. التأكد من صحة الرمز
        if ($user->otp_code != $request->otp_code) {
            return response()->json(['status' => 'error', 'message' => 'الرمز غير صحيح.'], 401);
        }

        // 4. التأكد من صلاحية الوقت
        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'الرمز منتهي الصلاحية، يرجى طلب رمز جديد.'], 401);
        }

        // 5. تصفير حقول الرمز للأمان وتحديث حالة الحساب
        $user->update([
            'otp_code'          => null,
            'otp_expires_at'    => null,
            'phone_verified_at' => $request->has('phone') ? Carbon::now() : $user->phone_verified_at,
            'email_verified_at' => $request->has('email') ? Carbon::now() : $user->email_verified_at,
        ]);

        // 6. إصدار التوكن (يمكنك تغيير الاسم حسب الصلاحيات vendor أو customer)
        $token = $user->createToken('vendor_venue_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'تم التحقق بنجاح، أهلاً بك عزيزي ' . $user->name,
            'user'    => $user,
            'token'   => $token
        ]);
    }
}
