<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TrainStatusReport extends Model
{
    protected $fillable = ['train_id', 'user_id', 'status', 'delay_minutes', 'note'];

    public const STATUSES = ['on_time', 'delayed', 'cancelled'];
    public const WINDOW_HOURS = 3;

    /**
     * خلاصة بلاغات الركّاب لقطار خلال آخر ٣ ساعات، أو null لو مفيش بلاغات.
     *
     * @return array{headline:string, status:string, delay:int|null, count:int, last_ago:string, recent:array}|null
     */
    public static function summaryFor(int $trainId): ?array
    {
        $reports = static::where('train_id', $trainId)
            ->where('created_at', '>=', Carbon::now()->subHours(self::WINDOW_HOURS))
            ->latest()
            ->get();

        if ($reports->isEmpty()) {
            return null;
        }

        $delayed = $reports->where('status', 'delayed');
        $cancelled = $reports->where('status', 'cancelled');
        $onTime = $reports->where('status', 'on_time');

        // متوسط التأخير (وسيط) من بلاغات التأخير
        $delays = $delayed->pluck('delay_minutes')->filter()->sort()->values();
        $median = $delays->isNotEmpty() ? (int) round($delays[(int) floor($delays->count() / 2)]) : null;

        // العنوان: الإلغاء أولًا، ثم التأخير لو غالب، وإلا في الموعد
        if ($cancelled->count() > $onTime->count() && $cancelled->count() >= $delayed->count()) {
            $status = 'cancelled';
            $headline = 'بلاغات بإلغاء/توقّف القطار';
        } elseif ($delayed->count() >= $onTime->count() && $delayed->isNotEmpty()) {
            $status = 'delayed';
            $headline = $median ? "متأخر ~{$median} دقيقة" : 'متأخر';
        } else {
            $status = 'on_time';
            $headline = 'في الموعد';
        }

        return [
            'headline' => $headline,
            'status' => $status,
            'delay' => $median,
            'count' => $reports->count(),
            'last_ago' => $reports->first()->created_at->diffForHumans(),
            'recent' => $reports->take(5)->map(fn ($r) => [
                'status' => $r->status,
                'delay' => $r->delay_minutes,
                'note' => $r->note,
                'ago' => $r->created_at->diffForHumans(),
            ])->all(),
        ];
    }

    /** أيام النافذة التاريخية لحساب المصداقية. */
    public const RELIABILITY_DAYS = 90;

    /** أقل عدد بلاغات نعرض عندها مؤشر المصداقية (عشان يبقى ذو معنى). */
    public const RELIABILITY_MIN = 3;

    /**
     * مؤشر مصداقية القطر من بلاغات الركّاب خلال آخر ٩٠ يوم، أو null لو البلاغات قليلة.
     *
     * @return array{on_time_pct:int, median_delay:int|null, count:int, on_time:int, delayed:int, cancelled:int}|null
     */
    public static function reliabilityFor(int $trainId): ?array
    {
        $reports = static::where('train_id', $trainId)
            ->where('created_at', '>=', Carbon::now()->subDays(self::RELIABILITY_DAYS))
            ->get(['status', 'delay_minutes']);

        if ($reports->count() < self::RELIABILITY_MIN) {
            return null;
        }

        $onTime = $reports->where('status', 'on_time')->count();
        $delayed = $reports->where('status', 'delayed');
        $cancelled = $reports->where('status', 'cancelled')->count();
        $total = $reports->count();

        $delays = $delayed->pluck('delay_minutes')->filter()->sort()->values();
        $median = $delays->isNotEmpty() ? (int) round($delays[(int) floor($delays->count() / 2)]) : null;

        return [
            'on_time_pct' => (int) round($onTime / $total * 100),
            'median_delay' => $median,
            'count' => $total,
            'on_time' => $onTime,
            'delayed' => $delayed->count(),
            'cancelled' => $cancelled,
        ];
    }
}
