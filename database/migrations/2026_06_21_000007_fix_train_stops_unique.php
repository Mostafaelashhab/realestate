<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * القطار قد يمرّ بنفس المحطة أكثر من مرة (رجوع/تبادل)، فقيد التفرّد على
 * (train_id, station_id) خاطئ. نستبدله بتفرّد على (train_id, stop_order).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_stops', function (Blueprint $table) {
            $table->dropUnique(['train_id', 'station_id']);
            $table->unique(['train_id', 'stop_order']);
        });
    }

    public function down(): void
    {
        Schema::table('train_stops', function (Blueprint $table) {
            $table->dropUnique(['train_id', 'stop_order']);
            $table->unique(['train_id', 'station_id']);
        });
    }
};
