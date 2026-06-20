<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('stop_order');         // ترتيب المحطة على الخط
            $table->time('arrival_time')->nullable();           // موعد الوصول (null لمحطة البداية)
            $table->time('departure_time')->nullable();         // موعد القيام (null لمحطة النهاية)
            $table->unsignedTinyInteger('arrival_day_offset')->default(0);   // 0 نفس اليوم، 1 اليوم التالي
            $table->unsignedTinyInteger('departure_day_offset')->default(0);
            $table->decimal('distance_km', 7, 2)->default(0);   // المسافة التراكمية من محطة البداية
            $table->timestamps();

            $table->unique(['train_id', 'station_id']);
            $table->index(['train_id', 'stop_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_stops');
    }
};
