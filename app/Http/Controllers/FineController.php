<?php

namespace App\Http\Controllers;

use App\Models\Fine;

class FineController extends Controller
{
    public function index()
    {
        $fines = Fine::orderBy('sort')->get()->groupBy('category');

        $categories = [
            'tickets' => 'مخالفات التذاكر',
            'conduct' => 'مخالفات السلوك والسلامة',
            'general' => 'عام',
        ];

        return view('fines', compact('fines', 'categories'));
    }
}
