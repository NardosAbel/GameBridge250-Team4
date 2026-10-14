<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes (Inertia + React)
|--------------------------------------------------------------------------
*/

// Public Landing Page
Route::get('/', function () {
    return Inertia::render('Landing');
})->name('home');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');

    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Verification notice & actions
Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verification.notice');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verification.verify');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('verification.resend');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Phase 2 Social & Gaming Pages
    Route::get('/feed', function () {
        return Inertia::render('Social/Feed');
    })->name('feed');

    Route::get('/teams', function () {
        return Inertia::render('Teams/Index');
    })->name('teams.index');

    Route::get('/teams/{id}', function ($id) {
        return Inertia::render('Teams/Show', ['teamId' => (int) $id]);
    })->name('teams.show');

    Route::get('/communities', function () {
        return Inertia::render('Communities/Index');
    })->name('communities.index');

    Route::get('/communities/{id}', function ($id) {
        return Inertia::render('Communities/Show', ['communityId' => (int) $id]);
    })->name('communities.show');

    Route::get('/recruitment', function () {
        return Inertia::render('Recruitment/Index');
    })->name('recruitment.index');

    Route::get('/chat', function () {
        return Inertia::render('Chat/Index');
    })->name('chat.index');

    Route::get('/notifications', function () {
        return Inertia::render('Notifications/Index');
    })->name('notifications.index');

    Route::get('/moderation/queue', function () {
        return Inertia::render('Moderation/Queue');
    })->name('moderation.queue');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
