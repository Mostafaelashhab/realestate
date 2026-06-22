<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['push_subscriptions', 'standing_alerts', 'train_reminders'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['push_subscriptions', 'standing_alerts', 'train_reminders'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
        }
    }
};
