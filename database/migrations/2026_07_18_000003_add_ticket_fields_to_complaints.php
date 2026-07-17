<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** توحيد الفيد: بوست "سوق تذاكر" داخل نفس جدول البوستات (حقول اختيارية). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'from_station_id')) {
                $table->foreignId('from_station_id')->nullable()->after('train_id')->constrained('stations')->nullOnDelete();
            }
            if (! Schema::hasColumn('complaints', 'to_station_id')) {
                $table->foreignId('to_station_id')->nullable()->after('from_station_id')->constrained('stations')->nullOnDelete();
            }
            if (! Schema::hasColumn('complaints', 'travel_date')) {
                $table->date('travel_date')->nullable()->after('to_station_id');
            }
            if (! Schema::hasColumn('complaints', 'price_egp')) {
                $table->unsignedInteger('price_egp')->nullable()->after('travel_date');
            }
            if (! Schema::hasColumn('complaints', 'contact')) {
                $table->string('contact', 60)->nullable()->after('price_egp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            foreach (['from_station_id', 'to_station_id', 'travel_date', 'price_egp', 'contact'] as $col) {
                if (Schema::hasColumn('complaints', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
