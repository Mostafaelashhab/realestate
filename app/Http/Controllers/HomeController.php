<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Train;

class HomeController extends Controller
{
    public function index()
    {
        $stations = Station::orderBy('name_ar')->get();
        $trainCount = Train::count();

        return view('home', compact('stations', 'trainCount'));
    }
}
