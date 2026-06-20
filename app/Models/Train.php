<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Train extends Model
{
    protected $fillable = [
        'number', 'type', 'type_ar', 'official_type', 'name', 'runs_on', 'active', 'source', 'source_updated_at',
    ];

    protected $casts = [
        'runs_on' => 'array',
        'active' => 'boolean',
        'source_updated_at' => 'date',
    ];

    /** الأنواع المعروفة لقطارات السكة الحديد المصرية. */
    public const TYPES = [
        'vip' => 'VIP',
        'spanish' => 'إسباني مكيف',
        'talgo' => 'تالجو',
        'improved' => 'مميز مكيف',
        'ac' => 'مكيف',
        'russian' => 'روسي مكيف',
        'ordinary' => 'عادي (درجة ثالثة)',
    ];

    public function stops(): HasMany
    {
        return $this->hasMany(TrainStop::class)->orderBy('stop_order');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(TrainClass::class);
    }

    public function fares(): HasMany
    {
        return $this->hasMany(Fare::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->official_type ?: ($this->type_ar ?: (self::TYPES[$this->type] ?? $this->type));
    }

    /** هل يعمل القطار في يوم معيّن (0=الأحد ... 6=السبت حسب Carbon dayOfWeek). */
    public function runsOnDay(int $dayOfWeek): bool
    {
        if (empty($this->runs_on)) {
            return true; // يوميًا
        }

        return in_array($dayOfWeek, $this->runs_on, true);
    }
}
