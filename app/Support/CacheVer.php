<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * إبطال جماعي للكاش عبر "رقم نسخة" بدل Cache tags
 * (لأن درايفر الملفات/قاعدة البيانات على الاستضافة المشتركة لا يدعم tags).
 *
 * الاستخدام:
 *   Cache::remember(CacheVer::key('catalog', "train:$id"), 43200, fn() => ...);
 *   CacheVer::bump('catalog'); // يبطّل كل مفاتيح هذه المجموعة دفعة واحدة
 */
class CacheVer
{
    public static function version(string $group): int
    {
        return (int) Cache::rememberForever("ver:$group", fn () => 1);
    }

    /** مفتاح كاش مرتبط برقم نسخة المجموعة. */
    public static function key(string $group, string $key): string
    {
        return "{$group}:v".self::version($group).":{$key}";
    }

    /** يبطّل كل مفاتيح المجموعة (بزيادة رقم النسخة). */
    public static function bump(string $group): void
    {
        self::version($group); // يضمن وجود المفتاح
        Cache::increment("ver:$group");
    }
}
