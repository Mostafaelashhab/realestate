<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_listings')) {
            return;
        }

        Schema::create('ticket_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->foreignId('to_station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->date('travel_date')->nullable();
            $table->string('train_number', 20)->nullable();
            $table->string('class_ar', 60)->nullable();
            $table->unsignedTinyInteger('seats')->default(1);
            $table->unsignedInteger('price_egp')->nullable();
            $table->string('kind', 10)->default('sale'); // sale | swap
            $table->string('contact', 60);              // موبايل/واتساب
            $table->text('note')->nullable();
            $table->string('status', 10)->default('active'); // active | closed
            $table->timestamps();
            $table->index(['status', 'travel_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_listings');
    }
};
