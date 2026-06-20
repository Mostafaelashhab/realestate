<?php

namespace App\Services;

use App\Models\Train;
use App\Support\Format;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * يقدّر مكان القطار "دلوقتي" بناءً على جدول المواعيد فقط (مفيش tracking حقيقي).
 * الحساب زمني بالكامل: نفترض إن القطار قام من محطة البداية في معاده، ونحدّد
 * هو المفروض بين أي محطتين الآن، ونستكمل موقعه خطيًا بالنسبة للزمن.
 *
 * النتيجة تقديرية واسترشادية وليست الموقع الفعلي للقطار.
 */
class TrainPositionEstimator
{
    public function estimate(Train $train, ?CarbonInterface $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();

        $stops = $train->relationLoaded('stops')
            ? $train->stops
            : $train->stops()->with('station')->get();

        if ($stops->count() < 2) {
            return $this->result('unknown', 'لا توجد بيانات كافية لهذا القطار.');
        }

        // نجرب رحلة بدأت اليوم أو أمس (لتغطية القطارات الليلية).
        foreach ([0, 1] as $daysBack) {
            $base = $now->copy()->startOfDay()->subDays($daysBack);
            $timeline = $this->buildTimeline($stops, $base);

            $origin = $timeline[0];
            $terminal = $timeline[count($timeline) - 1];

            if ($now->lt($origin['departure']) || $now->gt($terminal['arrival'])) {
                continue;
            }

            return $this->locateWithin($timeline, $now);
        }

        // مفيش رحلة شغّالة الآن → اعرض الموعد القادم.
        $todayTimeline = $this->buildTimeline($stops, $now->copy()->startOfDay());
        $origin = $todayTimeline[0];

        if ($now->lt($origin['departure'])) {
            return $this->result(
                'before',
                'لم يتحرك القطار بعد. القيام من '.$origin['name'].' الساعة '.Format::time($origin['departure']).'.',
                ['next_station' => $origin['name'], 'eta' => Format::time($origin['departure'])]
            );
        }

        return $this->result('idle', 'القطار أنهى رحلة اليوم. الرحلة القادمة غدًا.');
    }

    private function buildTimeline($stops, Carbon $base): array
    {
        $timeline = [];

        foreach ($stops as $stop) {
            $arrival = $stop->arrival_time ? $this->at($base, $stop->arrival_time, $stop->arrival_day_offset) : null;
            $departure = $stop->departure_time ? $this->at($base, $stop->departure_time, $stop->departure_day_offset) : null;

            $timeline[] = [
                'name' => $stop->station->name_ar,
                'arrival' => $arrival ?? $departure,
                'departure' => $departure ?? $arrival,
                'map_x' => $stop->map_x,
                'map_y' => $stop->map_y,
                'lat' => $stop->station->lat,
                'lng' => $stop->station->lng,
            ];
        }

        return $timeline;
    }

    private function at(Carbon $base, string $time, int $dayOffset): Carbon
    {
        [$h, $m] = array_pad(explode(':', $time), 2, 0);

        return $base->copy()->addDays($dayOffset)->setTime((int) $h, (int) $m);
    }

    private function locateWithin(array $timeline, Carbon $now): array
    {
        $count = count($timeline);
        $origin = $timeline[0]['departure'];
        $terminal = $timeline[$count - 1]['arrival'];
        $overall = $this->progress($origin, $terminal, $now);

        for ($i = 0; $i < $count; $i++) {
            $stop = $timeline[$i];

            // واقف في المحطة الآن؟ (ليست محطة البداية)
            if ($i !== 0 && $now->betweenIncluded($stop['arrival'], $stop['departure'])) {
                $next = $timeline[$i + 1] ?? null;

                return $this->result('at_station', 'القطار الآن في محطة '.$stop['name'].'.', [
                    'current_station' => $stop['name'],
                    'next_station' => $next['name'] ?? null,
                    'eta' => Format::time($next['arrival'] ?? null),
                    'map_x' => $stop['map_x'],
                    'map_y' => $stop['map_y'],
                    'lat' => $stop['lat'],
                    'lng' => $stop['lng'],
                    'overall_progress' => $overall,
                ]);
            }

            // متحرك بين هذه المحطة واللي بعدها؟
            $next = $timeline[$i + 1] ?? null;
            if ($next && $now->betweenIncluded($stop['departure'], $next['arrival'])) {
                $ratio = $this->ratio($stop['departure'], $next['arrival'], $now);

                return $this->result('running', 'القطار الآن بين محطتي '.$stop['name'].' و'.$next['name'].'.', [
                    'from_station' => $stop['name'],
                    'next_station' => $next['name'],
                    'eta' => Format::time($next['arrival']),
                    'segment_progress' => (int) round($ratio * 100),
                    'overall_progress' => $overall,
                    'map_x' => $this->lerp($stop['map_x'], $next['map_x'], $ratio),
                    'map_y' => $this->lerp($stop['map_y'], $next['map_y'], $ratio),
                    'lat' => $this->lerp($stop['lat'], $next['lat'], $ratio),
                    'lng' => $this->lerp($stop['lng'], $next['lng'], $ratio),
                ]);
            }
        }

        return $this->result('unknown', 'تعذّر تقدير موقع القطار حاليًا.');
    }

    private function ratio(Carbon $from, Carbon $to, Carbon $now): float
    {
        $span = $from->diffInSeconds($to) ?: 1;

        return min(1, max(0, $from->diffInSeconds($now) / $span));
    }

    private function progress(Carbon $from, Carbon $to, Carbon $now): int
    {
        return (int) round($this->ratio($from, $to, $now) * 100);
    }

    private function lerp($from, $to, float $ratio): ?float
    {
        if ($from === null || $to === null) {
            return null;
        }

        return round($from + ($to - $from) * $ratio, 6);
    }

    private function result(string $status, string $message, array $extra = []): array
    {
        return array_merge([
            'status' => $status,
            'message' => $message,
            'current_station' => null,
            'from_station' => null,
            'next_station' => null,
            'eta' => null,
            'segment_progress' => null,
            'overall_progress' => null,
            'map_x' => null,
            'map_y' => null,
            'lat' => null,
            'lng' => null,
            'is_estimate' => true,
        ], $extra);
    }
}
