<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('type');                       // schedule | price | other
            $table->string('train_number')->nullable();   // رقم القطار المعني (اختياري)
            $table->text('message');                       // وصف المشكلة
            $table->string('contact')->nullable();         // وسيلة تواصل اختيارية
            $table->string('status')->default('new');      // new | reviewed | resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
