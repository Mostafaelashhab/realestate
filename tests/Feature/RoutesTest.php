<?php

use App\Models\Station;
use App\Models\Train;
use Database\Seeders\EgyptRailwaySeeder;

beforeEach(function () {
    $this->seed(EgyptRailwaySeeder::class);
});

it('renders the home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('قطارات مصر');
});

it('renders the report page', function () {
    $this->get(route('report'))->assertOk()->assertSee('بلّغ عن خطأ');
});

it('renders the fines page', function () {
    $this->get(route('fines'))->assertOk()->assertSee('الغرامات');
});

it('renders a train detail page', function () {
    $train = Train::first();
    $this->get(route('trains.show', $train))->assertOk()->assertSee('جدول المحطات');
});

it('stores a submitted report', function () {
    $this->post(route('report.store'), [
        'type' => 'schedule',
        'train_number' => '948',
        'message' => 'ميعاد القيام غلط من بنها',
    ])->assertRedirect(route('report'))->assertSessionHas('status');

    $this->assertDatabaseHas('reports', [
        'type' => 'schedule',
        'train_number' => '948',
        'status' => 'new',
    ]);
});

it('validates report submissions', function () {
    $this->post(route('report.store'), ['type' => 'invalid', 'message' => 'x'])
        ->assertSessionHasErrors(['type', 'message']);
});

it('searches for trains between two stations', function () {
    $cai = Station::where('code', 'CAI')->first();
    $alx = Station::where('code', 'ALX')->first();

    $this->get(route('search', ['from' => $cai->id, 'to' => $alx->id]))
        ->assertOk()
        ->assertSee('الإسكندرية');
});

it('looks up a train by its number and redirects to it', function () {
    $train = Train::first();

    $this->get(route('trains.lookup', ['number' => $train->number]))
        ->assertRedirect(route('trains.show', $train));
});

it('shows an error when the train number is not found', function () {
    $this->get(route('trains.lookup', ['number' => '000000']))
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('number');
});

it('searches by train number and skips from/to validation', function () {
    $train = Train::first();

    $this->get(route('search', ['number' => $train->number]))
        ->assertRedirect(route('trains.show', $train));
});

it('rejects a search with identical stations', function () {
    $cai = Station::where('code', 'CAI')->first();

    $this->get(route('search', ['from' => $cai->id, 'to' => $cai->id]))
        ->assertSessionHasErrors('to');
});
