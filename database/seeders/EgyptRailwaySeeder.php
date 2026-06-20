<?php

namespace Database\Seeders;

use App\Models\Fine;
use App\Models\Station;
use App\Models\Train;
use App\Models\TrainClass;
use App\Models\TrainStop;
use Illuminate\Database\Seeder;

/**
 * بيانات أولية واقعية مبدئية لأهم خطوط السكة الحديد المصرية.
 * هذه البيانات للعرض والتجربة — حدّثها من المصدر الرسمي قبل الاعتماد عليها.
 */
class EgyptRailwaySeeder extends Seeder
{
    public function run(): void
    {
        $stations = $this->seedStations();
        $this->seedCairoAlexandria($stations);
        $this->seedCairoAswan($stations);
        $this->seedFines();
    }

    /** @return array<string, Station> */
    private function seedStations(): array
    {
        $data = [
            // code, name_ar, name_en, governorate, lat, lng
            ['CAI', 'القاهرة (رمسيس)', 'Cairo (Ramses)', 'القاهرة', 30.0626, 31.2497],
            ['GIZ', 'الجيزة', 'Giza', 'الجيزة', 30.0131, 31.2089],
            ['BNH', 'بنها', 'Banha', 'القليوبية', 30.4658, 31.1849],
            ['TNT', 'طنطا', 'Tanta', 'الغربية', 30.7865, 31.0004],
            ['DMN', 'دمنهور', 'Damanhour', 'البحيرة', 31.0341, 30.4682],
            ['ALX', 'الإسكندرية (محطة مصر)', 'Alexandria (Misr)', 'الإسكندرية', 31.1934, 29.9056],
            ['BSF', 'بني سويف', 'Beni Suef', 'بني سويف', 29.0744, 31.0978],
            ['MNY', 'المنيا', 'Minya', 'المنيا', 28.1099, 30.7503],
            ['AST', 'أسيوط', 'Asyut', 'أسيوط', 27.1809, 31.1837],
            ['SHG', 'سوهاج', 'Sohag', 'سوهاج', 26.5570, 31.6948],
            ['NHM', 'نجع حمادي', 'Nag Hammadi', 'قنا', 26.0489, 32.2419],
            ['QNA', 'قنا', 'Qena', 'قنا', 26.1551, 32.7160],
            ['LXR', 'الأقصر', 'Luxor', 'الأقصر', 25.6872, 32.6396],
            ['EDF', 'إدفو', 'Edfu', 'أسوان', 24.9781, 32.8730],
            ['KMB', 'كوم أمبو', 'Kom Ombo', 'أسوان', 24.4764, 32.9447],
            ['ASW', 'أسوان', 'Aswan', 'أسوان', 24.0934, 32.9070],
        ];

        $stations = [];
        foreach ($data as [$code, $ar, $en, $gov, $lat, $lng]) {
            $stations[$code] = Station::create([
                'code' => $code,
                'name_ar' => $ar,
                'name_en' => $en,
                'governorate' => $gov,
                'lat' => $lat,
                'lng' => $lng,
            ]);
        }

        return $stations;
    }

    private function seedCairoAlexandria(array $s): void
    {
        // قطار إسباني مكيف سريع (قليل التوقفات)
        $this->makeTrain($s, '935', 'spanish', 'إسباني مكيف — القاهرة/الإسكندرية', [
            ['CAI', null, '09:00', 0],
            ['TNT', '09:55', '09:58', 90],
            ['ALX', '11:30', null, 208],
        ], [
            ['first_ac', 120, 0.55],
            ['second_ac', 90, 0.40],
        ]);

        // قطار مكيف مميز (كل المحطات)
        $this->makeTrain($s, '703', 'improved', 'مميز مكيف — القاهرة/الإسكندرية', [
            ['CAI', null, '07:15', 0],
            ['BNH', '07:55', '07:57', 47],
            ['TNT', '08:35', '08:38', 90],
            ['DMN', '09:30', '09:32', 160],
            ['ALX', '10:25', null, 208],
        ], [
            ['first_ac', 90, 0.45],
            ['second_ac', 65, 0.32],
        ]);

        // عودة من الإسكندرية للقاهرة
        $this->makeTrain($s, '936', 'spanish', 'إسباني مكيف — الإسكندرية/القاهرة', [
            ['ALX', null, '17:00', 0],
            ['TNT', '18:30', '18:33', 118],
            ['CAI', '19:30', null, 208],
        ], [
            ['first_ac', 120, 0.55],
            ['second_ac', 90, 0.40],
        ]);
    }

    private function seedCairoAswan(array $s): void
    {
        // قطار VIP نهاري القاهرة → أسوان
        $this->makeTrain($s, '981', 'vip', 'VIP نهاري — القاهرة/أسوان', [
            ['CAI', null, '08:00', 0],
            ['GIZ', '08:12', '08:14', 5],
            ['BSF', '09:45', '09:48', 120],
            ['MNY', '11:20', '11:25', 245],
            ['AST', '13:05', '13:10', 375],
            ['SHG', '14:40', '14:45', 467],
            ['QNA', '16:30', '16:35', 600],
            ['LXR', '17:35', '17:40', 670],
            ['KMB', '19:30', '19:33', 818],
            ['ASW', '20:30', null, 879],
        ], [
            ['first_ac', 150, 0.45],
            ['second_ac', 110, 0.32],
        ]);

        // قطار ليلي (عربات نوم) القاهرة → أسوان
        $this->makeTrain($s, '985', 'spanish', 'مكيف ليلي — القاهرة/أسوان', [
            ['CAI', null, '21:00', 0],
            ['GIZ', '21:12', '21:14', 5],
            ['BSF', '22:45', '22:48', 120],
            ['MNY', '00:30', '00:35', 245, 1, 1],
            ['AST', '02:15', '02:20', 375, 1, 1],
            ['SHG', '03:50', '03:55', 467, 1, 1],
            ['QNA', '05:40', '05:45', 600, 1, 1],
            ['LXR', '06:50', '06:55', 670, 1, 1],
            ['ASW', '09:30', null, 879, 1],
        ], [
            ['sleeper', 600, 0.0],
            ['first_ac', 140, 0.40],
            ['second_ac', 100, 0.28],
        ]);
    }

    /**
     * @param  array<int, array{0:string,1:?string,2:?string,3:float,4?:int,5?:int}>  $stops
     * @param  array<int, array{0:string,1:float,2:float}>  $classes
     */
    private function makeTrain(array $s, string $number, string $type, string $name, array $stops, array $classes): void
    {
        $train = Train::create([
            'number' => $number,
            'type' => $type,
            'name' => $name,
        ]);

        foreach ($stops as $i => $stop) {
            [$code, $arr, $dep, $dist] = $stop;
            $arrOffset = $stop[4] ?? 0;
            $depOffset = $stop[5] ?? $arrOffset;

            TrainStop::create([
                'train_id' => $train->id,
                'station_id' => $s[$code]->id,
                'stop_order' => $i + 1,
                'arrival_time' => $arr,
                'departure_time' => $dep,
                'arrival_day_offset' => $arrOffset,
                'departure_day_offset' => $depOffset,
                'distance_km' => $dist,
            ]);
        }

        foreach ($classes as [$key, $base, $perKm]) {
            TrainClass::create([
                'train_id' => $train->id,
                'class_key' => $key,
                'base_fare' => $base,
                'per_km' => $perKm,
            ]);
        }
    }

    private function seedFines(): void
    {
        $fines = [
            ['الركوب بدون تذكرة', 'يُحصّل من الراكب قيمة التذكرة كاملة بالإضافة إلى غرامة تعادل قيمتها.', 'قيمة التذكرة + مثلها غرامة', 'tickets', 1],
            ['ركوب درجة أعلى من التذكرة', 'دفع فرق الدرجة بالإضافة إلى غرامة على فرق القيمة.', 'فرق الدرجة + غرامة', 'tickets', 2],
            ['تجاوز محطة الوصول', 'دفع قيمة المسافة الزائدة عن الوجهة المدوّنة بالتذكرة مع غرامة.', 'فرق المسافة + غرامة', 'tickets', 3],
            ['التدخين داخل العربات', 'مخالفة لقرارات الهيئة وتُحصّل غرامة فورية.', 'غرامة مالية', 'conduct', 4],
            ['الركوب على الأسطح أو السلالم', 'سلوك خطر ومحظور قانونًا ويعرّض صاحبه للغرامة والمساءلة.', 'غرامة مالية + مساءلة', 'conduct', 5],
            ['إتلاف ممتلكات القطار', 'إلزام المتسبب بقيمة الإصلاح بالإضافة إلى غرامة.', 'قيمة التلف + غرامة', 'conduct', 6],
        ];

        foreach ($fines as [$title, $desc, $amount, $cat, $sort]) {
            Fine::create([
                'title' => $title,
                'description' => $desc,
                'amount_label' => $amount,
                'category' => $cat,
                'sort' => $sort,
            ]);
        }
    }
}
