<?php

// ─────────────────────────────────────────────────────────────────
// routes/web.php
// ─────────────────────────────────────────────────────────────────

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Management\Cell\CellController;
use App\Http\Controllers\Management\Inmate\InmateController;
use App\Http\Controllers\Management\Inmate\IncidentController;
use App\Http\Controllers\ArtificialIntelligence\AiAssistantController;
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

Route::get('/test-mail', function () {
    Mail::raw('Test email from IIMMS', function ($message) {
        $message->to('craigfrenan0@gmail.com')->subject('Brevo SMTP Test');
    });
    return 'Sent (or check storage/logs/laravel.log if it errored)';
});

// ── Admin ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get   ('/admin_dashboard',                 [AdminController::class, 'adminDashboard'])   ->name('admin.dashboard');

    // Inmates
    Route::post  ('/admin/inmates',                   [InmateController::class, 'store'])           ->name('inmates.store');
    Route::get   ('/admin/inmates/{inmate}',          [InmateController::class, 'show'])            ->name('inmates.show');
    Route::put   ('/admin/inmates/{inmate}',          [InmateController::class, 'update'])          ->name('inmates.update');

    // ── Cells ─────────────────────────────────────────────────────
    Route::get   ('/admin/cells',                     [CellController::class, 'index'])             ->name('admin.cells.index');
    Route::get   ('/admin/cells/data',                [CellController::class, 'data'])              ->name('admin.cells.data');       // ← JSON for JS
    Route::get   ('/admin/cells/next-block',          [CellController::class, 'nextBlock'])         ->name('admin.cells.next-block'); // ← JSON for add-cell.js
    Route::get   ('/admin/cells/add',                 [CellController::class, 'create'])            ->name('admin.cells.create');
    Route::post  ('/admin/cells',                     [CellController::class, 'store'])             ->name('admin.cells.store');
    Route::put   ('/admin/cells/{cell}',              [CellController::class, 'update'])            ->name('admin.cells.update');
    Route::put   ('/admin/blocks/{block}',            [CellController::class, 'updateBlock'])       ->name('admin.blocks.update');
    Route::delete('/admin/blocks/{block}',            [CellController::class, 'destroyBlock'])      ->name('admin.blocks.destroy');
    Route::get('/admin/cells/{id}/inmates',           [CellController::class, 'inmates'])           ->name('admin.cells.inmates');

    Route::get   ('/admin/ai-assistant',              function () { return view('ai-assistant'); })  ->name('ai-assistant');
    Route::get   ('/admin/ai-assistant/models',       [AiAssistantController::class, 'listModels'])  ->name('ai-assistant.models.index');
    Route::post  ('/admin/ai-assistant/models',       [AiAssistantController::class, 'setModel'])    ->name('ai-assistant.models.set');
    Route::post  ('/admin/ai-assistant/chat',         [AiAssistantController::class, 'chat'])        ->name('ai-assistant.chat');

    Route::get   ('/admin/incidents',                 [IncidentController::class, 'index'])          ->name('admin.incidents.index');
    Route::get   ('/admin/incidents/inmates',         [IncidentController::class, 'inmates'])        ->name('admin.incidents.inmates');
    Route::post  ('/admin/incidents',                 [IncidentController::class, 'store'])          ->name('admin.incidents.store');
});