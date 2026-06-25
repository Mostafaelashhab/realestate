<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrSnapshotController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeatWatchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StandingAlertController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TrainController;
use App\Http\Controllers\TrainReminderController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// المصادقة
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::view('/favorites', 'favorites')->name('favorites');
// صفحة المسار (SEO) — رابط دائم بالـ slug.
Route::get('/قطارات/{from}/{to}', [RouteController::class, 'show'])->name('route');
Route::get('/fines', [FineController::class, 'index'])->name('fines');
Route::get('/train-lookup', [TrainController::class, 'lookup'])->name('trains.lookup');
Route::get('/trains/{train}', [TrainController::class, 'show'])->name('trains.show');
Route::post('/trains/{train}/standing-alert', [StandingAlertController::class, 'store'])->middleware(['auth','throttle:10,1'])->name('trains.standing');

// تذكير بميعاد قطار.
Route::post('/trains/{train}/reminder', [TrainReminderController::class, 'store'])->middleware(['auth','throttle:10,1'])->name('trains.reminder');
Route::post('/reminders/{reminder}/cancel', [TrainReminderController::class, 'cancel'])->middleware('throttle:20,1')->name('reminders.cancel');

// مراقبة المقاعد (Premium): نبّهني أول ما يفضى كرسي.
Route::post('/trains/{train}/seat-watch', [SeatWatchController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('trains.seatwatch');
Route::post('/seat-watch/{watch}/cancel', [SeatWatchController::class, 'cancel'])->middleware(['auth', 'throttle:20,1'])->name('seatwatch.cancel');
Route::view('/premium', 'premium')->name('premium');

Route::get('/stations/{station}', [StationController::class, 'show'])->name('stations.show');

// التقاط بيانات الهيئة من متصفّح المستخدم (تحديث الأسعار والمواعيد تلقائيًا).
Route::post('/enr-snapshot', [EnrSnapshotController::class, 'store'])->middleware('throttle:30,1')->name('enr.snapshot');

// اشتراكات إشعارات الويب (Push).
Route::post('/push/subscribe', [PushController::class, 'subscribe'])->middleware(['auth','throttle:30,1'])->name('push.subscribe');
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
