<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->string('title');                 // عنوان المخالفة
            $table->text('description')->nullable();  // التفاصيل
            $table->string('amount_label')->nullable(); // قيمة الغرامة كنص (قد تكون مدى أو معادلة)
            $table->string('category')->default('general');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
