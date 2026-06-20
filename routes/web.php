<?php

use App\Http\Controllers\FineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TrainController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/live', [LiveController::class, 'index'])->name('live');
Route::get('/fines', [FineController::class, 'index'])->name('fines');
Route::get('/train-lookup', [TrainController::class, 'lookup'])->name('trains.lookup');
Route::get('/trains/{train}', [TrainController::class, 'show'])->name('trains.show');
Route::get('/trains/{train}/position', [TrainController::class, 'position'])->name('trains.position');

// مزامنة الأسعار الرسمية خلف رابط سري (الرمز من ENR_SYNC_TOKEN).
Route::get('/sync/{token}', [SyncController::class, 'index'])->name('sync');
Route::post('/sync/{token}/import', [SyncController::class, 'import'])->name('sync.import');
