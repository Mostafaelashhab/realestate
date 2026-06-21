<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('train_number');
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', fn (Blueprint $t) => $t->dropColumn('notified_at'));
    }
};
