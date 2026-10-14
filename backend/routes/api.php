<?php

use App\Domain\Profiles\Models\Game;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\v1\ChatController;
use App\Http\Controllers\Api\v1\CommentController;
use App\Http\Controllers\Api\v1\CommunityController;
use App\Http\Controllers\Api\v1\ModerationController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\PostController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\v1\RecruitmentController;
use App\Http\Controllers\Api\v1\SocialInteractionController;
use App\Http\Controllers\Api\v1\TeamController;
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

    // Public Profiles & Games
    Route::get('/profiles/public/{gamerTag}', [ProfileController::class, 'getByGamerTag'])
        ->middleware('throttle:60,1');
    Route::get('/games', function () {
        return response()->json(['data' => Game::where('is_active', true)->get()]);
    });

    // Public / Explore Feed & Listings
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::get('/teams', [TeamController::class, 'index']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);
    Route::get('/communities', [CommunityController::class, 'index']);
    Route::get('/communities/{community}', [CommunityController::class, 'show']);
    Route::get('/communities/{community}/posts', [CommunityController::class, 'posts']);
    Route::get('/recruitment', [RecruitmentController::class, 'index']);

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

        // Posts & Social Feed
        Route::post('/posts', [PostController::class, 'store']);
        Route::delete('/posts/{post}', [PostController::class, 'destroy']);
        Route::post('/posts/{post}/poll/vote', [PostController::class, 'votePoll']);
        Route::post('/posts/{post}/pin', [PostController::class, 'pin']);
        Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

        // Social Interactions
        Route::prefix('interactions')->group(function () {
            Route::post('/reaction/{type}/{id}', [SocialInteractionController::class, 'toggleReaction']);
            Route::post('/follow/{type}/{id}', [SocialInteractionController::class, 'toggleFollow']);
            Route::post('/share/post/{post}', [SocialInteractionController::class, 'share']);
            Route::post('/bookmark/{type}/{id}', [SocialInteractionController::class, 'toggleBookmark']);
            Route::post('/block/{user}', [SocialInteractionController::class, 'toggleBlock']);
            Route::post('/mute/{user}', [SocialInteractionController::class, 'toggleMute']);
        });

        // Teams & Rosters
        Route::post('/teams', [TeamController::class, 'store']);
        Route::put('/teams/{team}', [TeamController::class, 'update']);
        Route::post('/teams/{team}/invite', [TeamController::class, 'invite']);
        Route::post('/team-invitations/{invitation}/respond', [TeamController::class, 'respondInvitation']);
        Route::put('/team-members/{member}/role', [TeamController::class, 'updateMemberRole']);
        Route::delete('/team-members/{member}', [TeamController::class, 'removeMember']);
        Route::post('/teams/{team}/transfer-ownership', [TeamController::class, 'transferOwnership']);

        // Communities
        Route::post('/communities', [CommunityController::class, 'store']);
        Route::put('/communities/{community}', [CommunityController::class, 'update']);
        Route::post('/communities/{community}/join', [CommunityController::class, 'join']);
        Route::post('/community-members/{member}/approve', [CommunityController::class, 'approveMember']);

        // Recruitment Board
        Route::post('/recruitment', [RecruitmentController::class, 'store']);
        Route::post('/recruitment/{post}/close', [RecruitmentController::class, 'close']);

        // Team & Community Chat
        Route::prefix('chat')->group(function () {
            Route::get('/teams/{team}', [ChatController::class, 'teamChannel']);
            Route::get('/communities/{community}', [ChatController::class, 'communityChannel']);
            Route::get('/channels/{channel}/messages', [ChatController::class, 'messages']);
            Route::post('/channels/{channel}/messages', [ChatController::class, 'sendMessage']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::get('/preferences', [NotificationController::class, 'getPreferences']);
            Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
        });

        // Moderation
        Route::prefix('moderation')->group(function () {
            Route::post('/report', [ModerationController::class, 'report']);
            Route::get('/queue', [ModerationController::class, 'queue']);
            Route::post('/action', [ModerationController::class, 'action']);
        });

        // Audit Logs (Admin / Moderator only)
        Route::middleware(['permission:audit_logs.view'])->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
        });
    });
});
