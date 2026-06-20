<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->string('class_key');                  // first_ac, second_ac, ...
            $table->decimal('base_fare', 8, 2)->default(0);   // الحد الأدنى للتذكرة (جنيه)
            $table->decimal('per_km', 8, 4)->default(0);      // تعريفة الكيلومتر
            $table->timestamps();

            $table->unique(['train_id', 'class_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_classes');
    }
};
