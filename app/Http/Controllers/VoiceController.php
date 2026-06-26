<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Train;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * بحث صوتي: ياخد النص المنطوق (q) ويفهمه — رحلة "من X لـ Y"، أو رقم قطر،
 * أو محطة واحدة — ويحوّل للنتيجة المناسبة.
 */
class VoiceController extends Controller
{
    public function handle(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return view('voice'); // صفحة المساعد الصوتي
        }

        // أرقام عربية/هندية → لاتينية (لرقم القطار).
        $latin = strtr($q, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);

        // "قطر 935" أو رقم لوحده → بحث برقم القطار.
        if (preg_match('/(?:قطر|رقم)\s*(\d{1,5})/u', $latin, $m) || preg_match('/^\D*(\d{2,5})\D*$/', $latin, $m)) {
            if (Train::where('number', $m[1])->exists()) {
                return redirect()->route('search', ['number' => $m[1]]);
            }
        }

        $stations = Cache::remember(\App\Support\CacheVer::key('catalog', 'voice:stations'), now()->addHours(6),
            fn () => Station::whereNotNull('slug')->get(['id', 'name_ar', 'slug'])->all());

        // المحطات المذكورة بترتيب ظهورها في الكلام (يتعامل مع "لـ" و"الـ").
        $found = $this->stationsInText($q, $stations);

        // رحلة (محطتين فأكثر) → القيام = الأولى، الوصول = التانية.
        if (count($found) >= 2 && $found[0]->id !== $found[1]->id) {
            return redirect()->route('route', ['from' => $found[0]->slug, 'to' => $found[1]->slug]);
        }

        // محطة واحدة → صفحتها.
        if (count($found) === 1) {
            return redirect()->route('stations.show', $found[0]);
        }

        return redirect()->route('home')->with('voice_error', "مفهمتش «{$q}» — جرّب قول: «من بنها للقاهرة».");
    }

    /**
     * يرجّع المحطات المذكورة في النص مرتّبة حسب موضعها (بدون تكرار/تداخل).
     *
     * @param  array<int,Station>  $stations
     * @return array<int,Station>
     */
    private function stationsInText(string $text, array $stations): array
    {
        $t = $this->norm($text);
        // "للقاهره" = لـ + القاهره → نرجّع أداة التعريف عشان المطابقة تظبط.
        $t2 = str_replace('لل', 'ال', $t);
        if ($t === '') {
            return [];
        }

        $hits = [];
        foreach ($stations as $s) {
            $n = $this->norm($s->name_ar);
            if ($n === '') {
                continue;
            }
            $pos = mb_strpos($t, $n);
            if ($pos === false) {
                $pos = mb_strpos($t2, $n);
            }
            if ($pos !== false) {
                $hits[] = ['s' => $s, 'pos' => $pos, 'len' => mb_strlen($n)];
            }
        }

        // الأقرب للبداية أولًا، والأطول له الأولوية عند نفس الموضع.
        usort($hits, fn ($a, $b) => $a['pos'] <=> $b['pos'] ?: $b['len'] <=> $a['len']);

        $picked = [];
        $ranges = [];
        foreach ($hits as $h) {
            foreach ($picked as $p) {
                if ($p->id === $h['s']->id) {
                    continue 2;
                }
            }
            foreach ($ranges as [$st, $en]) {
                if (! ($h['pos'] + $h['len'] <= $st || $h['pos'] >= $en)) {
                    continue 2; // متداخل مع محطة اتاختارت
                }
            }
            $picked[] = $h['s'];
            $ranges[] = [$h['pos'], $h['pos'] + $h['len']];
            if (count($picked) >= 2) {
                break;
            }
        }

        return $picked;
    }

    private function norm(string $s): string
    {
        $s = preg_replace('/[إأآ]/u', 'ا', $s);
        $s = str_replace(['ى', 'ة'], ['ي', 'ه'], $s);
        $s = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $s); // تشكيل

        return trim($s);
    }
}
