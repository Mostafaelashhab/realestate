<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    protected $fillable = [
        'egytrains_id', 'enr_id', 'name_ar', 'name_en', 'code', 'station_code',
        'booking_name', 'governorate', 'lat', 'lng', 'slug',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (Station $station) {
            if (empty($station->slug) && $station->name_ar) {
                $station->slug = static::slugify($station->name_ar);
            }
        });
    }

    /** سلَج عربي صديق للروابط: يحذف الأقواس والتشكيل ويحوّل المسافات لشرطات. */
    public static function slugify(string $name): string
    {
        $s = preg_replace('/[()\[\]]/u', '', $name);
        $s = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $s); // تشكيل
        $s = preg_replace('/\s+/u', '-', trim($s));

        return trim($s, '-');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TrainStop::class);
    }

    public function getNameAttribute(): string
    {
        return $this->name_ar;
    }
}
