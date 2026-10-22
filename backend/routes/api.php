<?php

use App\Domain\Profiles\Models\Game;
use App\Http\Controllers\Api\V1\Administration\AdminDashboardController;
use App\Http\Controllers\Api\V1\Administration\CreatorAnalyticsController;
use App\Http\Controllers\Api\V1\Administration\PlatformAnalyticsController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\DisputeController;
use App\Http\Controllers\Api\V1\Education\AwarenessCampaignController;
use App\Http\Controllers\Api\V1\Education\CertificateController;
use App\Http\Controllers\Api\V1\Education\CourseController;
use App\Http\Controllers\Api\V1\Education\EducationDashboardController;
use App\Http\Controllers\Api\V1\Education\LearningPathController;
use App\Http\Controllers\Api\V1\Education\LessonController;
use App\Http\Controllers\Api\V1\Education\QuizController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\ModerationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\RecruitmentController;
use App\Http\Controllers\Api\V1\SocialInteractionController;
use App\Http\Controllers\Api\V1\Sponsorship\BrandProfileController;
use App\Http\Controllers\Api\V1\Sponsorship\DeliverableController;
use App\Http\Controllers\Api\V1\Sponsorship\PrizePoolController;
use App\Http\Controllers\Api\V1\Sponsorship\SponsorshipController;
use App\Http\Controllers\Api\V1\Sponsorship\SponsorshipPackageController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TournamentController;
use App\Http\Controllers\Api\V1\VenueController;
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

    // Public Tournament Engine Endpoints
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::get('/tournaments/{tournament}/participants', [TournamentController::class, 'participants']);
    Route::get('/matches/{match}', [MatchController::class, 'show']);
    Route::get('/rankings', [RankingController::class, 'index']);
    Route::get('/rankings/leaderboards', [RankingController::class, 'leaderboards']);
    Route::get('/rankings/snapshots', [RankingController::class, 'snapshots']);

    // Public Venue, Event & Ticketing Endpoints
    Route::get('/venues', [VenueController::class, 'index']);
    Route::get('/venues/{venue}', [VenueController::class, 'show']);
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/tickets/types', [TicketController::class, 'ticketTypes']);

    // Public Payment Webhook (rate-limited against DoS)
    Route::post('/payments/webhook/{provider}', [PaymentController::class, 'webhook'])
        ->middleware('throttle:100,1');

    // Public Digital Awareness & Education Hub
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/learning-paths', [LearningPathController::class, 'index']);
    Route::get('/learning-paths/{path}', [LearningPathController::class, 'show']);
    Route::get('/campaigns', [AwarenessCampaignController::class, 'index']);
    Route::get('/campaigns/{campaign}', [AwarenessCampaignController::class, 'show']);
    Route::post('/campaigns/{campaign}/share', [AwarenessCampaignController::class, 'share']);
    Route::get('/certificates/verify/{code}', [CertificateController::class, 'verify']);
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show']);

    // Public Sponsorship & Prize Pool Endpoints
    Route::get('/sponsors/brands', [BrandProfileController::class, 'index']);
    Route::get('/sponsors/brands/{brandProfile}', [BrandProfileController::class, 'show']);
    Route::get('/sponsors/packages', [SponsorshipPackageController::class, 'index']);
    Route::get('/sponsors/packages/{sponsorshipPackage}', [SponsorshipPackageController::class, 'show']);
    Route::get('/tournaments/{tournament}/prize-pool', [PrizePoolController::class, 'progress']);

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

        // Tournament Engine Management
        Route::prefix('tournaments')->group(function () {
            Route::post('/', [TournamentController::class, 'store']);
            Route::put('/{tournament}', [TournamentController::class, 'update']);
            Route::delete('/{tournament}', [TournamentController::class, 'destroy']);
            Route::put('/{tournament}/status', [TournamentController::class, 'updateStatus']);
            Route::post('/{tournament}/register', [TournamentController::class, 'register']);
            Route::post('/{tournament}/check-in', [TournamentController::class, 'checkIn']);
            Route::put('/{tournament}/seeding', [TournamentController::class, 'updateSeeding']);
            Route::post('/{tournament}/bracket', [TournamentController::class, 'generateBracket']);
            Route::post('/{tournament}/staff', [TournamentController::class, 'assignStaff']);
        });

        // Matches, Check-in, Results & Overrides
        Route::prefix('matches')->group(function () {
            Route::post('/{match}/check-in', [MatchController::class, 'checkIn']);
            Route::put('/{match}/room', [MatchController::class, 'updateRoom']);
            Route::post('/{match}/result', [MatchController::class, 'submitResult']);
            Route::post('/{match}/confirm', [MatchController::class, 'confirmResult']);
            Route::post('/{match}/override', [MatchController::class, 'overrideResult']);
            Route::post('/{match}/disputes', [DisputeController::class, 'store']);
        });

        // Disputes
        Route::prefix('disputes')->group(function () {
            Route::get('/', [DisputeController::class, 'index']);
            Route::get('/{dispute}', [DisputeController::class, 'show']);
            Route::post('/{dispute}/resolve', [DisputeController::class, 'resolve']);
        });

        // Rankings & Snapshots
        Route::prefix('rankings')->group(function () {
            Route::post('/snapshot', [RankingController::class, 'snapshot']);
        });

        // Venues & Events Management
        Route::prefix('venues')->group(function () {
            Route::post('/', [VenueController::class, 'store']);
            Route::put('/{venue}', [VenueController::class, 'update']);
            Route::post('/{venue}/staff', [VenueController::class, 'assignStaff']);
            Route::post('/{venue}/reviews', [VenueController::class, 'storeReview']);
            Route::get('/{venue}/analytics', [VenueController::class, 'analytics']);
        });

        Route::prefix('events')->group(function () {
            Route::post('/', [EventController::class, 'store']);
            Route::put('/{event}', [EventController::class, 'update']);
        });

        // Ticketing Operations & Scanner Gate
        Route::prefix('tickets')->group(function () {
            Route::post('/types', [TicketController::class, 'storeTicketType']);
            Route::post('/checkout', [TicketController::class, 'checkout'])->middleware('throttle:30,1');
            Route::get('/my-tickets', [TicketController::class, 'myTickets']);
            Route::get('/reports', [TicketController::class, 'reports']);
            Route::get('/reports/export', [TicketController::class, 'exportCsv']);
            Route::post('/scan', [TicketController::class, 'scan'])->middleware('throttle:120,1');
            Route::post('/sync-scans', [TicketController::class, 'syncOfflineScans'])->middleware('throttle:60,1');
            Route::get('/{ticket}', [TicketController::class, 'showTicket']);
            Route::post('/{ticket}/transfer', [TicketController::class, 'transfer']);
        });

        // Payments, Refunds & Organizer Payouts
        Route::prefix('payments')->group(function () {
            Route::post('/initiate', [PaymentController::class, 'initiate'])->middleware('throttle:30,1');
            Route::post('/{payment}/reconcile', [PaymentController::class, 'reconcile']);
            Route::post('/orders/{order}/refund', [PaymentController::class, 'requestRefund']);
            Route::post('/refunds/{refund}/process', [PaymentController::class, 'processRefund']);
            Route::post('/payouts', [PaymentController::class, 'requestPayout']);
            Route::post('/payouts/{payout}/process', [PaymentController::class, 'processPayout']);
        });

        // Digital Awareness & Learning Hub
        Route::prefix('education')->group(function () {
            Route::get('/dashboard', [EducationDashboardController::class, 'index']);
            Route::post('/courses', [CourseController::class, 'store']);
            Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll']);
            Route::post('/courses/{course}/publish', [CourseController::class, 'publish']);
            Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
            Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete']);
            Route::get('/lessons/{lesson}/offline-package', [LessonController::class, 'offlinePackage']);
            Route::post('/lessons/sync-offline', [LessonController::class, 'syncOffline']);
            Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
            Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit']);
            Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);
            Route::post('/learning-paths', [LearningPathController::class, 'store']);
            Route::post('/learning-paths/{path}/assign', [LearningPathController::class, 'assign']);
            Route::post('/campaigns', [AwarenessCampaignController::class, 'store']);
            Route::get('/campaigns/{campaign}/analytics', [AwarenessCampaignController::class, 'analytics']);
        });

        // Audit Logs (Admin / Moderator only)
        Route::middleware(['permission:audit_logs.view'])->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
        });

        // Phase 6: Sponsorship Marketplace & Brand Deals
        Route::prefix('sponsors')->group(function () {
            Route::post('/brands', [BrandProfileController::class, 'store']);
            Route::put('/brands/{brandProfile}', [BrandProfileController::class, 'update']);
            Route::post('/packages', [SponsorshipPackageController::class, 'store']);
        });

        Route::prefix('sponsorships')->group(function () {
            Route::get('/', [SponsorshipController::class, 'index']);
            Route::post('/apply', [SponsorshipController::class, 'apply']);
            Route::get('/{sponsorship}', [SponsorshipController::class, 'show']);
            Route::post('/{sponsorship}/review', [SponsorshipController::class, 'review']);
            Route::get('/{sponsorship}/report', [SponsorshipController::class, 'report']);
            Route::get('/{sponsorship}/report/export', [SponsorshipController::class, 'exportReport']);
            Route::post('/deliverables/{deliverable}/evidence', [DeliverableController::class, 'submitEvidence']);
            Route::post('/deliverables/{deliverable}/verify', [DeliverableController::class, 'verify']);
        });

        Route::post('/tournaments/{tournament}/prize-pool/contribute', [PrizePoolController::class, 'contribute']);

        // Phase 6: Creator Tools & Performance Insights
        Route::prefix('creator')->group(function () {
            Route::get('/insights', [CreatorAnalyticsController::class, 'insights']);
        });

        // Phase 6: Platform Analytics & System Administration
        Route::prefix('admin')->group(function () {
            Route::get('/overview', [AdminDashboardController::class, 'overview']);
            Route::get('/analytics', [PlatformAnalyticsController::class, 'metrics']);
            Route::get('/analytics/export', [PlatformAnalyticsController::class, 'exportCsv']);
            Route::get('/feature-flags', [AdminDashboardController::class, 'featureFlags']);
            Route::put('/feature-flags/{key}', [AdminDashboardController::class, 'toggleFlag']);
            Route::get('/system-health', [AdminDashboardController::class, 'systemHealth']);
        });
    });
});
