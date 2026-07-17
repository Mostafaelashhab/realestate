<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('complaints')) {
            Schema::create('complaints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('train_id')->nullable()->constrained()->nullOnDelete();
                $table->string('category', 40)->default('general'); // general | delay | cleanliness | crowding | staff | other
                $table->text('body');
                $table->timestamps();
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('complaint_likes')) {
            Schema::create('complaint_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['complaint_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_likes');
        Schema::dropIfExists('complaints');
    }
};
