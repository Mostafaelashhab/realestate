<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Promo extends Model
{
    protected $fillable = [
        'title', 'body', 'url', 'variant', 'active', 'starts_at', 'ends_at', 'sort',
    ];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort' => 'integer',
    ];

    public const VARIANTS = [
        'rail' => 'أخضر',
        'amber' => 'برتقالي',
        'sky' => 'أزرق',
    ];

    /** العروض الظاهرة الآن: مفعّلة وضمن نافذة التاريخ. */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort')
            ->orderByDesc('id');
    }
}
