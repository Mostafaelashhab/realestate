<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->unsignedSmallInteger('lead_minutes')->default(60);
            $table->date('notified_for')->nullable(); // آخر يوم اتبعت فيه تذكير (لمنع التكرار في نفس اليوم)
            $table->string('status')->default('active'); // active | cancelled
            $table->timestamps();

            $table->index('status');
            $table->unique(['push_subscription_id', 'train_id', 'from_station_id'], 'reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_reminders');
    }
};
