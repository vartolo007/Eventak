<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            // تقييم واحد فقط لكل مناسبة (unique)
            $table->foreignId('event_id')->unique()->constrained('events')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('rating'); // من 1 إلى 5 نجوم
            $table->text('comment')->nullable();   // تعليق اختياري مع التقييم
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
