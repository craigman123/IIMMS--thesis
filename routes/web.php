<?php

// ─────────────────────────────────────────────────────────────────
// routes/web.php
// ─────────────────────────────────────────────────────────────────

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InmateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Show login/register page
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// Handle form submissions
Route::post('/login',    [AuthController::class, 'login'])->name('login.post');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout',   [AuthController::class, 'logout'])->name('logout');

// ── OTP Email Verification ────────────────────────────────────────
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])
    ->name('auth.send-otp')
    ->middleware('throttle:5,1');

Route::post('/auth/confirm-otp', [AuthController::class, 'confirmOtp'])
    ->name('auth.confirm-otp')
    ->middleware('throttle:10,1');

// ── Admin Routes ──────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin_dashboard', [AdminController::class, 'adminDashboard'])->name('admin.dashboard');

    // Inmate
    Route::post('/admin/inmates', [InmateController::class, 'store'])->name('inmates.store');
});