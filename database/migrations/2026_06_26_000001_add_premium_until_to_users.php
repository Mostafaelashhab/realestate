<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'premium_until')) {
            return; // العمود موجود بالفعل (محاولة سابقة) — نتجنّب خطأ التكرار.
        }

        Schema::table('users', function (Blueprint $table) {
            // تاريخ انتهاء اشتراك Premium (null = مجاني). يُفعَّل بالدفع أو يدويًا.
            $table->timestamp('premium_until')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('premium_until');
        });
    }
};
