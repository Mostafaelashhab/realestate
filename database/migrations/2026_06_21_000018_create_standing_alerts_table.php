<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standing_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('to_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('push_subscription_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->timestamp('depart_at');              // قيام القطار من محطة الركوب
            $table->string('status')->default('active'); // active | notified | expired
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'depart_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standing_alerts');
    }
};
