<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_status_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->string('status');                         // on_time | delayed | cancelled
            $table->unsignedSmallInteger('delay_minutes')->nullable();
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->index(['train_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_status_reports');
    }
};
