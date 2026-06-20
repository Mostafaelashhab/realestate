<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trains', function (Blueprint $table) {
            // النوع/الدرجات كما في النظام الرسمي (يُشتق من درجات الأسعار المستوردة).
            $table->string('official_type')->nullable()->after('type_ar');
        });
    }

    public function down(): void
    {
        Schema::table('trains', fn (Blueprint $t) => $t->dropColumn('official_type'));
    }
};
