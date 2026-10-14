<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Campus Esports Ethiopia - API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Campus Esports Ethiopia API v1 is healthy.',
            'timestamp' => now()->toISOString(),
            'platform' => config('app.name'),
        ]);
    });

    // Public Authentication & OTP Endpoints (rate-limited)
    Route::prefix('auth')->middleware('throttle:15,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/otp/send', [AuthController::class, 'sendOtp']);
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
        Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    });

    // Public Profiles
    Route::get('/profiles/public/{gamerTag}', [ProfileController::class, 'getByGamerTag'])
        ->middleware('throttle:60,1');

    // Authenticated Routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        // Current User & Session
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        // User Profile Management
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
        });

        // Moderation & Audit Logs (Admin / Moderator only)
        Route::middleware(['permission:audit_logs.view'])->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
        });
    });
});
