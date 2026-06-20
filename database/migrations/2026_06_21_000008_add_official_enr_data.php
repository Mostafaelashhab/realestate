<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('enr_id')->nullable()->unique()->after('egytrains_id'); // معرّف المحطة في النظام الرسمي
            $table->string('station_code')->nullable()->after('code');               // كود المحطة الرسمي
            $table->string('booking_name')->nullable()->after('name_en');            // الاسم المستخدم في رابط الحجز (CAIRO)
        });

        // الأسعار الرسمية لكل درجة بين محطتين على قطار معيّن (من نظام الحجز).
        Schema::create('fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('train_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('to_station_id')->constrained('stations')->cascadeOnDelete();
            $table->string('class_code');           // GA 2 ...
            $table->string('class_ar');             // ثالثة تهوية
            $table->unsignedInteger('price_piasters'); // السعر بالقروش (÷100 = جنيه)
            $table->string('currency')->default('EGP');
            $table->unsignedSmallInteger('distance_km')->nullable();
            $table->timestamps();

            $table->unique(['train_id', 'from_station_id', 'to_station_id', 'class_code'], 'fares_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fares');
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['enr_id', 'station_code', 'booking_name']);
        });
    }
};
