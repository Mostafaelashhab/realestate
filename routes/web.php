<?php

use App\Http\Controllers\FineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TrainController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/fines', [FineController::class, 'index'])->name('fines');
Route::get('/train-lookup', [TrainController::class, 'lookup'])->name('trains.lookup');
Route::get('/trains/{train}', [TrainController::class, 'show'])->name('trains.show');

// الإبلاغ عن خطأ في ميعاد أو سعر أو مشكلة عامة.
Route::get('/report', [ReportController::class, 'create'])->name('report');
Route::post('/report', [ReportController::class, 'store'])->name('report.store');

// مزامنة الأسعار الرسمية خلف رابط سري (الرمز من ENR_SYNC_TOKEN).
Route::get('/sync/{token}', [SyncController::class, 'index'])->name('sync');
Route::post('/sync/{token}/import', [SyncController::class, 'import'])->name('sync.import');
