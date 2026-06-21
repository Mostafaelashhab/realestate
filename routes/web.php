<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TrainController;
use App\Http\Controllers\TrainStatusController;
use App\Http\Controllers\TripShareController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::view('/favorites', 'favorites')->name('favorites');
// صفحة المسار (SEO) — رابط دائم بالـ slug.
Route::get('/قطارات/{from}/{to}', [RouteController::class, 'show'])->name('route');
Route::get('/fines', [FineController::class, 'index'])->name('fines');
Route::get('/train-lookup', [TrainController::class, 'lookup'])->name('trains.lookup');
Route::get('/trains/{train}', [TrainController::class, 'show'])->name('trains.show');
Route::get('/trains/{train}/status', [TrainStatusController::class, 'show'])->name('trains.status');
Route::post('/trains/{train}/status', [TrainStatusController::class, 'store'])->middleware('throttle:6,1')->name('trains.status.store');
Route::get('/stations/{station}', [StationController::class, 'show'])->name('stations.show');

// مشاركة الرحلة لحظيًا مع الأهل.
Route::post('/trip', [TripShareController::class, 'start'])->middleware('throttle:15,1')->name('trip.start');
Route::get('/trip/{trip}', [TripShareController::class, 'show'])->name('trip.show');
Route::get('/trip/{trip}/state', [TripShareController::class, 'state'])->name('trip.state');
Route::post('/trip/{trip}/ping', [TripShareController::class, 'ping'])->middleware('throttle:120,1')->name('trip.ping');
Route::post('/trip/{trip}/stop', [TripShareController::class, 'stop'])->name('trip.stop');

// اشتراكات إشعارات الويب (Push).
Route::post('/push/subscribe', [PushController::class, 'subscribe'])->middleware('throttle:30,1')->name('push.subscribe');
Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');

// الإبلاغ عن خطأ في ميعاد أو سعر أو مشكلة عامة.
Route::get('/report', [ReportController::class, 'create'])->name('report');
Route::post('/report', [ReportController::class, 'store'])->middleware('throttle:8,1')->name('report.store');

// لوحة المشرف الموحّدة + الأدوات (خلف رمز المزامنة).
Route::get('/admin/{token}', [AdminController::class, 'index'])->name('admin');

// لوحة المشرف للبلاغات (خلف رابط سري — رمز المزامنة).
Route::get('/admin/reports/{token}', [ReportController::class, 'admin'])->name('reports.admin');
Route::post('/admin/reports/{token}/{report}/status', [ReportController::class, 'updateStatus'])->name('reports.status');

// لوحة المشرف للعروض/البانرات.
Route::get('/admin/promos/{token}', [PromoController::class, 'admin'])->name('promos.admin');
Route::post('/admin/promos/{token}', [PromoController::class, 'store'])->name('promos.store');
Route::post('/admin/promos/{token}/{promo}/toggle', [PromoController::class, 'toggle'])->name('promos.toggle');
Route::delete('/admin/promos/{token}/{promo}', [PromoController::class, 'destroy'])->name('promos.destroy');

// مزامنة الأسعار الرسمية خلف رابط سري (الرمز من ENR_SYNC_TOKEN).
Route::get('/sync/{token}', [SyncController::class, 'index'])->name('sync');
Route::post('/sync/{token}/import', [SyncController::class, 'import'])->name('sync.import');
