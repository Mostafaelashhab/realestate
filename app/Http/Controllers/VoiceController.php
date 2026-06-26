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
            return redirect()->route('home');
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

        // "من X (إلى|لـ|ل) Y"
        $from = $to = null;
        if (preg_match('/من\s+(.+?)\s+(?:إلى|الى|الي|لحد|ل)\s*(.+)/u', $q, $m)) {
            $from = $this->match($m[1], $stations);
            $to = $this->match($m[2], $stations);
        }

        if ($from && $to && $from->id !== $to->id) {
            return redirect()->route('route', ['from' => $from->slug, 'to' => $to->slug]);
        }

        // محطة واحدة فقط → صفحتها.
        $single = $this->match($q, $stations);
        if ($single) {
            return redirect()->route('stations.show', $single);
        }

        return redirect()->route('home')->with('voice_error', "مفهمتش «{$q}» — جرّب قول: «من بنها للقاهرة».");
    }

    /** أفضل محطة مطابقة للنص (تطبيع عربي + أطول اسم متضمَّن). */
    private function match(string $text, array $stations): ?Station
    {
        $t = $this->norm($text);
        if ($t === '') {
            return null;
        }

        $best = null;
        $bestLen = 0;
        foreach ($stations as $s) {
            $n = $this->norm($s->name_ar);
            if ($n !== '' && (str_contains($t, $n) || str_contains($n, $t)) && mb_strlen($n) > $bestLen) {
                $best = $s;
                $bestLen = mb_strlen($n);
            }
        }

        return $best;
    }

    private function norm(string $s): string
    {
        $s = preg_replace('/[إأآ]/u', 'ا', $s);
        $s = str_replace(['ى', 'ة'], ['ي', 'ه'], $s);
        $s = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $s); // تشكيل

        return trim($s);
    }
}
