<?php

// ─────────────────────────────────────────────────────────────────
// routes/web.php
// ─────────────────────────────────────────────────────────────────

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CellController;
use App\Http\Controllers\InmateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// ── Login ─────────────────────────────────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout',   [AuthController::class, 'logout'])->name('logout');

// ── Login OTP (sent to the user's registered email after credentials pass) ───
Route::get  ('/login/verify',        [AuthController::class, 'showLoginOtp'])->name('login.otp');
Route::post ('/login/verify',        [AuthController::class, 'confirmLoginOtp'])->name('login.otp.confirm');
Route::post ('/login/resend-otp',    [AuthController::class, 'resendLoginOtp'])->name('login.otp.resend');

// ── OTP ───────────────────────────────────────────────────────────
Route::post('/auth/send-otp',    [AuthController::class, 'sendOtp'])->name('auth.send-otp')->middleware('throttle:5,1');
Route::post('/auth/confirm-otp', [AuthController::class, 'confirmOtp'])->name('auth.confirm-otp')->middleware('throttle:10,1');

// ── Admin ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/admin_dashboard', [AdminController::class, 'adminDashboard'])->name('admin.dashboard');

    // Inmates
    Route::post('/admin/inmates', [InmateController::class, 'store'])->name('inmates.store');

    // ── Cells ─────────────────────────────────────────────────────
    Route::get   ('/admin/cells',                     [CellController::class, 'index'])             ->name('admin.cells.index');
    Route::get   ('/admin/cells/data',                [CellController::class, 'data'])              ->name('admin.cells.data');       // ← JSON for JS
    Route::get   ('/admin/cells/next-block',          [CellController::class, 'nextBlock'])         ->name('admin.cells.next-block'); // ← JSON for add-cell.js
    Route::get   ('/admin/cells/add',                 [CellController::class, 'create'])            ->name('admin.cells.create');
    Route::post  ('/admin/cells',                     [CellController::class, 'store'])             ->name('admin.cells.store');
    Route::get   ('/admin/cells/{cell}/edit',         [CellController::class, 'edit'])              ->name('admin.cells.edit');
    Route::put   ('/admin/cells/{cell}',              [CellController::class, 'update'])            ->name('admin.cells.update');
    Route::delete('/admin/cells/{cell}',              [CellController::class, 'destroy'])           ->name('admin.cells.destroy');
    Route::patch ('/admin/cells/{cell}/maintenance',  [CellController::class, 'toggleMaintenance']) ->name('admin.cells.maintenance');
    Route::get('/admin/cells/{id}/inmates',           [CellController::class, 'inmates'])           ->name('admin.cells.inmates');

});