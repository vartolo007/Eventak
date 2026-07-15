<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_requests', function (Blueprint $table) {
            $table->id();

            // ربط الطلب بصاحب الصالة (المالك)
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // ربط الطلب بالصالة الأساسية (يكون Null إذا كان أول مرة ينشئ صالة)
            $table->foreignId('venue_id')->nullable()->constrained('venues')->onDelete('cascade');

            // نوع الطلب: create (صالة جديدة)، update (تعديل)، delete (حذف)
            $table->enum('type', ['create', 'update', 'delete'])->default('create');

            // البيانات المقترحة والمطلوب مراجعتها من الأدمن
            $table->string('name');
            $table->text('address');
            $table->integer('capacity');
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('images')->nullable(); // منسي
            // حالة الطلب: pending (معلق)، approved (مقبول)، rejected (مرفوض)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // حقل اختياري لتوضيح سبب الرفض من قبل الأدمن إذا رغب بذلك
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_requests');
    }
};
