<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->string('train_number');
            $table->string('from_enr');
            $table->string('to_enr');
            $table->string('from_name');
            $table->string('to_name');
            $table->date('service_date');
            $table->string('status')->default('active'); // active | expired | cancelled
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'service_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_watches');
    }
};
