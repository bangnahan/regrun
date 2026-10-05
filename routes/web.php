<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TripayCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Running Event Registration & Admin System
|--------------------------------------------------------------------------
*/

// Public Registration Multi-Step Wizard
Route::get('/', [RegistrationController::class, 'index'])->name('register.index');
Route::get('/event/{slug}', [RegistrationController::class, 'index'])->name('register.event');
Route::post('/register/participants', [RegistrationController::class, 'stepParticipants'])->name('register.step_participants');
Route::post('/register/checkout', [RegistrationController::class, 'stepCheckout'])->name('register.step_checkout');
Route::post('/register/pay', [RegistrationController::class, 'processPayment'])->name('register.process_payment');

// Order & Payment Status
Route::get('/order/{invoice}', [OrderController::class, 'show'])->name('order.show');
Route::post('/order/{invoice}/simulate-pay', [OrderController::class, 'simulatePay'])->name('order.simulate_pay');

// Tripay Webhook Callback
Route::post('/api/tripay/callback', [TripayCallbackController::class, 'handle'])->name('tripay.callback');
Route::post('/tripay/callback', [TripayCallbackController::class, 'handle']);

// Admin Authentication
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Admin Panel Protected Routes
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Modul Rekap Jersey Pabrik (Pivot Matrix & Export)
    Route::get('/jersey-recap', [AdminController::class, 'jerseyRecap'])->name('jersey_recap');
    Route::get('/jersey-recap/export', [AdminController::class, 'exportJerseyCsv'])->name('jersey_recap.export');

    // Manajemen Transaksi
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::post('/transactions/{invoice}/mark-paid', [AdminController::class, 'markAsPaid'])->name('transactions.mark_paid');
    Route::post('/transactions/{invoice}/resend-email', [AdminController::class, 'resendEmail'])->name('transactions.resend_email');

    // Manajemen Peserta
    Route::get('/participants', [AdminController::class, 'participants'])->name('participants');
    Route::post('/participants/{id}/toggle-rpc', [AdminController::class, 'toggleRacepack'])->name('participants.toggle_rpc');
    Route::get('/participants/export', [AdminController::class, 'exportParticipantsCsv'])->name('participants.export');

    // Manajemen Event & Kuota/Early Bird
    Route::get('/events', [AdminController::class, 'events'])->name('events');
    Route::post('/category/{id}/update', [AdminController::class, 'updateCategory'])->name('category.update');

    // Pengaturan Tripay & Mailketing
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'saveSettings'])->name('settings.save');
    Route::post('/settings/test-email', [AdminController::class, 'testEmail'])->name('settings.test_email');
});
