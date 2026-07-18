<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('complaint_comments', 'parent_id')) {
            Schema::table('complaint_comments', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('complaint_id')->constrained('complaint_comments')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('content_reports')) {
            Schema::create('content_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('complaint_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('comment_id')->nullable()->constrained('complaint_comments')->cascadeOnDelete();
                $table->string('reason', 200)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
        if (Schema::hasColumn('complaint_comments', 'parent_id')) {
            Schema::table('complaint_comments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
            });
        }
    }
};
