<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 16)->unique();        // رمز عام للمشاهدة (يُشارَك مع الأهل)
            $table->string('owner_token', 48)->unique();   // رمز سري للمالك (لإرسال الموقع/الإيقاف)
            $table->string('train_number')->nullable();
            $table->string('from_name')->nullable();
            $table->string('to_name')->nullable();
            $table->string('eta')->nullable();             // وقت الوصول المتوقّع (من الجدول)
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->decimal('last_speed', 6, 2)->nullable(); // م/ث (اختياري)
            $table->timestamp('last_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_shares');
    }
};
