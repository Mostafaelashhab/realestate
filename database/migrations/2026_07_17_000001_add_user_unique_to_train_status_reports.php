<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('train_status_reports', 'user_id')) {
            Schema::table('train_status_reports', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('train_id')
                    ->constrained()->nullOnDelete();
                // بلاغ واحد لكل مستخدم لكل قطر (يُحدَّث بدل ما يتكرر).
                $table->unique(['train_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('train_status_reports', 'user_id')) {
            Schema::table('train_status_reports', function (Blueprint $table) {
                $table->dropUnique(['train_id', 'user_id']);
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
