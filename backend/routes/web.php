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
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
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
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verification.notice');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verification.verify');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('verification.resend');
});

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

    // Phase 3 Tournament Engine Pages
    Route::get('/tournaments', function () {
        return Inertia::render('Tournaments/Index');
    })->name('tournaments.index');

    Route::get('/tournaments/{id}', function ($id) {
        return Inertia::render('Tournaments/Show', ['tournamentId' => (int) $id]);
    })->name('tournaments.show');

    Route::get('/tournaments/{id}/manage', function ($id) {
        return Inertia::render('Tournaments/Manage', ['tournamentId' => (int) $id]);
    })->name('tournaments.manage');

    Route::get('/rankings', function () {
        return Inertia::render('Rankings/Index');
    })->name('rankings.index');

    // Phase 4 Venues, Events, Ticketing & QR Gate Pages
    Route::get('/venues', function () {
        return Inertia::render('Venues/Index');
    })->name('venues.index');

    Route::get('/venues/{id}', function ($id) {
        return Inertia::render('Venues/Show', ['venueId' => (int) $id]);
    })->name('venues.show');

    Route::get('/events/{id}', function ($id) {
        return Inertia::render('Events/Show', ['eventId' => (int) $id]);
    })->name('events.show');

    Route::get('/my-tickets', function () {
        return Inertia::render('Tickets/MyTickets');
    })->name('tickets.my-tickets');

    Route::get('/scanner', function () {
        return Inertia::render('Tickets/Scanner');
    })->name('tickets.scanner');

    Route::get('/ticketing/reports', function () {
        return Inertia::render('Tickets/Reports');
    })->name('tickets.reports');

    // Phase 5 Digital Awareness and Learning Hub
    Route::get('/education', function () {
        return Inertia::render('Education/Index');
    })->name('education.index');

    Route::get('/education/dashboard', function () {
        return Inertia::render('Education/Dashboard');
    })->name('education.dashboard');

    Route::get('/education/courses/{id}', function ($id) {
        return Inertia::render('Education/Show', ['courseId' => $id]);
    })->name('education.course.show');

    Route::get('/education/lessons/{id}', function ($id) {
        return Inertia::render('Education/LessonShow', ['lessonId' => $id]);
    })->name('education.lesson.show');

    Route::get('/education/quizzes/{id}', function ($id) {
        return Inertia::render('Education/QuizShow', ['quizId' => $id]);
    })->name('education.quiz.show');

    Route::get('/education/campaigns/{id}', function ($id) {
        return Inertia::render('Education/Campaigns/Show', ['campaignId' => $id]);
    })->name('education.campaign.show');

    Route::get('/verify-certificate', function () {
        return Inertia::render('Education/VerifyCertificate');
    })->name('education.verify-certificate');

    // Phase 6 Sponsorship Marketplace, Creator Tools & Platform Analytics
    Route::get('/sponsors', function () {
        return Inertia::render('Sponsors/Index');
    })->name('sponsors.index');

    Route::get('/sponsors/dashboard', function () {
        return Inertia::render('Sponsors/Dashboard');
    })->name('sponsors.dashboard');

    Route::get('/creator/dashboard', function () {
        return Inertia::render('Creator/Dashboard');
    })->name('creator.dashboard');

    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    Route::get('/tournaments/{id}/prize-pool', function ($id) {
        return Inertia::render('Tournaments/PrizePool', ['tournamentId' => (int) $id]);
    })->name('tournaments.prize-pool');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
