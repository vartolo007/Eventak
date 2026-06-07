    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /*
        * Run the migrations.
        */
        public function up(): void
        {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id(); // المعرّف الفريد لكل عملية توليد رمز

                // 🔀 ربط الجدول بجدول المستخدمين (User ID)
                // إذا حُذف اليوزر من السيستم، تُحذف كل رموز الـ OTP الخاصة به تلقائياً بفضل onDelete('cascade')
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

                // 🔢 حقل حفظ الرمز نفسه (مكون من 4 أرقام)
                $table->string('code');

                // ⏳ وقت انتهاء صلاحية الرمز (من نوع وقت وتاريخ)
                $table->timestamp('expires_at');

                // 🔄 حالة الرمز: هل تم استخدامه لتسجيل الدخول أم لسه؟ (القيمة الافتراضية: false أي غير مستخدم)
                $table->boolean('is_used')->default(false);

                $table->timestamps(); // حقول لارافيل التلقائية لوقت إنشاء وتعديل السجل (created_at & updated_at)
            });
        }

        /*
        * Reverse the migrations.
        */
        public function down(): void
        {
            Schema::dropIfExists('otp_codes');
        }
    };
