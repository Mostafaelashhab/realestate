<?php

use App\Models\Fare;
use App\Models\Station;
use App\Models\Train;

beforeEach(fn () => config(['enr.sync_token' => 'secret']));

it('blocks the sync page without a valid token', function () {
    $this->get('/sync/wrong-token')->assertNotFound();
});

it('shows the sync page with the valid token', function () {
    $this->get('/sync/secret')->assertOk()->assertSee('مزامنة الأسعار الرسمية');
});

it('imports a search payload posted from the browser', function () {
    Station::create(['enr_id' => 'B', 'name_ar' => 'بنها', 'booking_name' => 'BANHA']);
    Station::create(['enr_id' => 'C', 'name_ar' => 'القاهره', 'booking_name' => 'CAIRO']);
    $train = Train::create(['number' => '948', 'type' => 'مكيف']);

    $payload = [[
        'steps' => [[
            'currency' => 'EGP',
            'totalDistance' => 45,
            'route' => [['id' => 'B'], ['id' => 'C']],
            'train' => [
                'name' => '948',
                'servicePoints' => [['coachClass' => ['id' => '1', 'shortName' => 'AC 3', 'localizationMap' => ['ar' => 'ثالثة مكيفة']]]],
                'fields' => [['key' => 'enr_train_description', 'localizationMap' => ['ar' => 'ثالثة مكيفة بحري']]],
            ],
        ]],
        'classesCostMap' => ['1' => 3000],
    ]];

    $this->postJson('/sync/secret/import', $payload)
        ->assertOk()
        ->assertJson(['saved' => 1]);

    expect(Fare::where('train_id', $train->id)->first()->price)->toBe(30.0)
        ->and($train->fresh()->official_type)->toBe('ثالثة مكيفة بحري');
});

it('rejects an invalid import payload', function () {
    $this->postJson('/sync/secret/import', ['nonsense' => true])->assertStatus(422);
});
