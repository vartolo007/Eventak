<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز الدخول - Eventak</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* تحسينات إضافية للمتصفحات الحديثة */
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif !important;
        }
        .otp-container {
            position: relative;
            background: #ffffff !important;
            border: 2px solid #6366f1 !important;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15) !important;
            transition: all 0.3s ease;
        }
        .main-card {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f8fafc; min-height: 100vh;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc; padding: 50px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="main-card"
                    style="max-width: 550px; background-color: #ffffff; border-radius: 24px; overflow: hidden;">

                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 45px 30px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 700; letter-spacing: 3px; font-family: 'Cairo', sans-serif;">
                                EVENTAK
                            </h1>
                            <p style="color: #c7d2fe; margin: 10px 0 0 0; font-size: 14px; font-weight: 400;">بوابتك الشاملة لأروع الفعاليات</p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 50px 40px 40px 40px;">
                            <h2 style="color: #1e293b; margin-top: 0; font-size: 26px; font-weight: 700;">مرحباً بك! 👋</h2>
                            
                            <p style="color: #64748b; font-size: 16px; line-height: 1.8; margin-bottom: 40px; font-weight: 400;">
                                لقد طلبت تسجيل الدخول إلى حسابك بأمان. يرجى استخدام رمز التحقق المؤقت الموضح أدناه لإتمام هذه العملية.
                            </p>

                            <div class="otp-container" style="background-color: #f1f5f9; border-radius: 16px; padding: 20px 30px; margin: 0 auto 40px auto; max-width: 280px; text-align: center;">
                                <span style="font-family: 'Courier New', Courier, monospace; font-size: 42px; font-weight: 800; color: #4f46e5; letter-spacing: 12px; display: block; margin-right: -12px;">
                                    {{ $otpCode }}
                                </span>
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fffbeb; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                                <tr>
                                    <td>
                                        <p style="color: #b45309; font-size: 13px; line-height: 1.6; margin: 0; text-align: center; font-weight: 600;">
                                            ⏱️ هذا الرمز صالح لمدة <span style="color: #dc2626; font-weight: 700;">5 دقائق</span> فقط.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0;">
                                إذا لم تكن أنت من طلب هذا الرمز، يمكنك تجاهل هذه الرسالة بأمان؛ لم يتم إجراء أي تغيير على حسابك بعد.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 25px; border-top: 1px solid #f1f5f9;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0; font-weight: 400;">
                                &copy; {{ date('Y') }} منصة Eventak. جميع الحقوق محفوظة.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>