<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'anonymous')) {
                $table->boolean('anonymous')->default(false)->after('user_id');
            }
            if (! Schema::hasColumn('complaints', 'poll_options')) {
                $table->json('poll_options')->nullable()->after('body');
            }
        });

        if (! Schema::hasTable('poll_votes')) {
            Schema::create('poll_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('choice'); // فهرس الخيار (0-based)
                $table->timestamps();
                $table->unique(['complaint_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::table('complaints', function (Blueprint $table) {
            foreach (['anonymous', 'poll_options'] as $col) {
                if (Schema::hasColumn('complaints', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
