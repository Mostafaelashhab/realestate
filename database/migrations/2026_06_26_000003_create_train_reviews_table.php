<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('train_reviews')) {
            return;
        }

        Schema::create('train_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->text('comment')->nullable();
            $table->timestamps();

            // تقييم واحد لكل مستخدم لكل قطار (يُحدَّث لو أعاد التقييم).
            $table->unique(['train_id', 'user_id']);
            $table->index('train_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_reviews');
    }
};
