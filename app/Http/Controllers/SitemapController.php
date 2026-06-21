<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Train;

class SitemapController extends Controller
{
    /** خريطة الموقع لمحركات البحث: الصفحات الثابتة + كل القطارات والمحطات. */
    public function index()
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'freq' => 'daily'],
            ['loc' => route('fines'), 'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => route('report'), 'priority' => '0.3', 'freq' => 'monthly'],
        ];

        foreach (Train::orderBy('id')->pluck('id') as $id) {
            $urls[] = ['loc' => route('trains.show', $id), 'priority' => '0.7', 'freq' => 'daily'];
        }

        foreach (Station::whereHas('stops')->orderBy('id')->pluck('id') as $id) {
            $urls[] = ['loc' => route('stations.show', $id), 'priority' => '0.6', 'freq' => 'daily'];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
