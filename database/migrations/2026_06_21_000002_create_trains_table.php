<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trains', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();          // رقم القطار
            $table->string('type')->default('ac');        // نوع القطار (مفتاح)
            $table->string('name')->nullable();           // اسم/وصف
            $table->json('runs_on')->nullable();          // أيام التشغيل [0..6] أو null = يوميًا
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trains');
    }
};
