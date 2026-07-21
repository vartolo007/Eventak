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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('event_name');
            $table->string('event_type');
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('guests_count');
            $table->decimal('total_price', 10, 2)->default(0.00); // السعر المحسوب برمجياً
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->text('note')->nullable();
            // إدارة الحالات مدمجة بالكامل هنا لتسهيل الـ Workflow
            $table->enum('status', ['pending', 'venue_pending', 'vendor_pending', 'confirmed', 'paid', 'completed', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable(); // سبب الرفض الإلزامي في حال تم رفض طلب الحجز
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
