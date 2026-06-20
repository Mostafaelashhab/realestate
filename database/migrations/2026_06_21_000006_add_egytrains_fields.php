<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->unsignedInteger('egytrains_id')->nullable()->unique()->after('id');
        });

        Schema::table('trains', function (Blueprint $table) {
            $table->string('type_ar')->nullable()->after('type'); // نوع القطار كما ورد بالعربي
            $table->string('source')->nullable()->after('active'); // مصدر البيانات
            $table->date('source_updated_at')->nullable()->after('source');
        });

        Schema::table('train_stops', function (Blueprint $table) {
            $table->decimal('map_x', 10, 4)->nullable()->after('distance_km'); // إحداثي تخطيطي س
            $table->decimal('map_y', 10, 4)->nullable()->after('map_x');        // إحداثي تخطيطي ص
        });
    }

    public function down(): void
    {
        Schema::table('stations', fn (Blueprint $t) => $t->dropColumn('egytrains_id'));
        Schema::table('trains', fn (Blueprint $t) => $t->dropColumn(['type_ar', 'source', 'source_updated_at']));
        Schema::table('train_stops', fn (Blueprint $t) => $t->dropColumn(['map_x', 'map_y']));
    }
};
