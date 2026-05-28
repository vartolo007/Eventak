<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز الدخول - Eventak</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color: #f4f7f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width: 600px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;">

                    <tr>
                        <td align="center" style="background-color: #1a202c; padding: 30px;">
                            <h1
                                style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold; letter-spacing: 2px;">
                                Eventak</h1>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 40px 30px;">
                            <h2 style="color: #2d3748; margin-top: 0; font-size: 24px;">مرحباً بك!</h2>
                            <p style="color: #718096; font-size: 16px; line-height: 1.6; margin-bottom: 35px;">
                                لقد طلبت تسجيل الدخول إلى حسابك. يرجى استخدام رمز التحقق أدناه لإتمام العملية.
                            </p>

                            <div
                                style="background-color: #f7fafc; border: 2px dashed #cbd5e0; border-radius: 10px; padding: 25px; margin: 0 auto 35px auto; max-width: 300px;">
                                <span
                                    style="font-family: 'Courier New', Courier, monospace; font-size: 48px; font-weight: bold; color: #2b6cb0; letter-spacing: 15px; display: block; text-align: center;">
                                    {{ $otpCode }}
                                </span>
                            </div>

                            <p style="color: #a0aec0; font-size: 14px; line-height: 1.5;">
                                هذا الرمز صالح لمدة <strong style="color: #e53e3e;">5 دقائق</strong> فقط.<br>
                                إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة لحماية حسابك.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center"
                            style="background-color: #f7fafc; padding: 20px; border-top: 1px solid #edf2f7;">
                            <p style="color: #a0aec0; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} نظام Eventak. جميع الحقوق محفوظة.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
