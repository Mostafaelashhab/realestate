<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class Format
{
    /** يحوّل الوقت إلى صيغة 12 ساعة بالعربي (مثال: 9:05 ص / 5:30 م). */
    public static function time(string|CarbonInterface|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $c = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $c->format('g:i').' '.($c->hour < 12 ? 'ص' : 'م');
    }
}
