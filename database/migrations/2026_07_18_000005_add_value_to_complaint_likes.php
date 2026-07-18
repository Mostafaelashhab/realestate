<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** like/dislike: 1 = إعجاب · -1 = عدم إعجاب. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('complaint_likes', 'value')) {
            Schema::table('complaint_likes', function (Blueprint $table) {
                $table->tinyInteger('value')->default(1)->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('complaint_likes', 'value')) {
            Schema::table('complaint_likes', function (Blueprint $table) {
                $table->dropColumn('value');
            });
        }
    }
};
