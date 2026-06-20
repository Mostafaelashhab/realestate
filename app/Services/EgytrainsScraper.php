<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * يسحب بيانات القطارات من egytrains.com.
 *
 * الموقع يبني على Next.js ويضمّن البيانات في وسم <script id="__NEXT_DATA__">،
 * فنستخرج الـ JSON منه مباشرة بدل تحليل الـ HTML. النسخة العربية للروابط
 * (/قطار/{رقم}) تعطي أسماء المحطات وأنواع القطارات بالعربي.
 */
class EgytrainsScraper
{
    private const BASE = 'https://egytrains.com';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /**
     * يجمع كل أرقام القطارات من ملفات sitemap.
     *
     * @return array<int, int>
     */
    public function trainNumbers(): array
    {
        $numbers = [];

        $index = $this->get(self::BASE.'/sitemap.xml');
        preg_match_all('#<loc>([^<]*sitemap-\d+\.xml)</loc>#', (string) $index, $maps);

        $sitemaps = $maps[1] ?: [self::BASE.'/sitemap.xml'];

        foreach ($sitemaps as $sitemap) {
            $xml = $this->get($sitemap);
            preg_match_all('#/train/(\d+)(?:["<])#', (string) $xml, $m);
            foreach ($m[1] as $n) {
                $numbers[(int) $n] = (int) $n;
            }
        }

        $numbers = array_values($numbers);
        sort($numbers);

        return $numbers;
    }

    /** يجلب ويحلّل صفحة قطار واحد بالعربي. */
    public function fetchTrain(int $number): ?array
    {
        $url = self::BASE.'/'.rawurlencode('قطار').'/'.$number;
        $html = $this->get($url);

        return $html ? $this->parseTrainHtml($html) : null;
    }

    /**
     * يحوّل HTML صفحة القطار إلى مصفوفة منظّمة (دالة نقية قابلة للاختبار).
     *
     * @return array{number:string, type:string, working:bool, last_update:?string, stops:array}|null
     */
    public function parseTrainHtml(string $html): ?array
    {
        if (! preg_match('#<script id="__NEXT_DATA__" type="application/json">(.*?)</script>#s', $html, $m)) {
            return null;
        }

        $json = json_decode($m[1], true);
        $data = $json['props']['pageProps']['data'] ?? null;

        if (! is_array($data) || empty($data['cities'])) {
            return null;
        }

        // إحداثيات الخريطة التخطيطية مفهرسة بمعرّف المحطة.
        $map = [];
        foreach ($data['map'] ?? [] as $point) {
            if (isset($point['id'])) {
                $map[$point['id']] = ['x' => $point['cx'] ?? null, 'y' => $point['cy'] ?? null];
            }
        }

        $stops = [];
        $prevMinutes = null;
        $dayOffset = 0;

        foreach (array_values($data['cities']) as $order => $city) {
            $arrival = $city['a'] ?? null;
            $departure = $city['d'] ?? null;

            // استنتاج تجاوز منتصف الليل: لو الوقت رجع لأقل من السابق، زوّد اليوم.
            $marker = $arrival ?? $departure;
            if ($marker !== null) {
                $minutes = $this->toMinutes($marker);
                if ($prevMinutes !== null && $minutes < $prevMinutes) {
                    $dayOffset++;
                }
                $prevMinutes = $minutes;
            }

            $stops[] = [
                'egytrains_id' => $city['id'],
                'name_ar' => trim((string) $city['name']),
                'arrival' => $this->normalizeTime($arrival),
                'departure' => $this->normalizeTime($departure),
                'day_offset' => $dayOffset,
                'map_x' => $map[$city['id']]['x'] ?? null,
                'map_y' => $map[$city['id']]['y'] ?? null,
            ];
        }

        return [
            'number' => (string) ($data['name'] ?? ''),
            'type' => trim((string) ($data['type'] ?? '')),
            'working' => ($data['working'] ?? '') === '✅',
            'last_update' => $data['lastUpdate'] ?? null,
            'stops' => $stops,
        ];
    }

    private function normalizeTime(?string $time): ?string
    {
        if (! $time || ! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return null;
        }

        return $time;
    }

    private function toMinutes(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }

    private function get(string $url): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => self::UA,
            'Accept-Language' => 'ar',
        ])->timeout(30)->retry(2, 1000, throw: false)->get($url);

        return $response->successful() ? $response->body() : null;
    }
}
