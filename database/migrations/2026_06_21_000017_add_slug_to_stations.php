<?php

use App\Models\Station;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name_ar')->index();
        });

        // توليد سلَج فريد لكل محطة (إلحاق المعرّف عند التكرار).
        $used = [];
        Station::query()->orderBy('id')->each(function (Station $s) use (&$used) {
            $base = Station::slugify($s->name_ar ?? (string) $s->id) ?: (string) $s->id;
            $slug = $base;
            if (isset($used[$slug])) {
                $slug = $base.'-'.$s->id;
            }
            $used[$slug] = true;
            $s->newQuery()->whereKey($s->id)->update(['slug' => $slug]);
        });
    }

    public function down(): void
    {
        Schema::table('stations', fn (Blueprint $t) => $t->dropColumn('slug'));
    }
};
