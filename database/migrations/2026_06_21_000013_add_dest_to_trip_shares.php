<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_shares', function (Blueprint $table) {
            $table->decimal('to_lat', 10, 7)->nullable()->after('eta');
            $table->decimal('to_lng', 10, 7)->nullable()->after('to_lat');
        });
    }

    public function down(): void
    {
        Schema::table('trip_shares', fn (Blueprint $t) => $t->dropColumn(['to_lat', 'to_lng']));
    }
};
