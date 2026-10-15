<?php

namespace App\Providers;

use App\Domain\Administration\Policies\AdminDashboardPolicy;
use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Communities\Models\Community;
use App\Domain\Education\Models\AwarenessCampaign;
use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\LearningPath;
use App\Domain\Education\Models\Lesson;
use App\Domain\Education\Models\Quiz;
use App\Domain\Education\Policies\AwarenessCampaignPolicy;
use App\Domain\Education\Policies\CertificatePolicy;
use App\Domain\Education\Policies\CoursePolicy;
use App\Domain\Education\Policies\LearningPathPolicy;
use App\Domain\Education\Policies\LessonPolicy;
use App\Domain\Education\Policies\QuizPolicy;
use App\Domain\Moderation\Models\ModerationCase;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Payout;
use App\Domain\Payments\Models\Refund;
use App\Domain\Payments\Policies\PaymentPolicy;
use App\Domain\Payments\Policies\PayoutPolicy;
use App\Domain\Payments\Policies\RefundPolicy;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Recruitment\Models\RecruitmentPost;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Models\Post;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Domain\Sponsorship\Policies\BrandProfilePolicy;
use App\Domain\Sponsorship\Policies\SponsorshipDeliverablePolicy;
use App\Domain\Sponsorship\Policies\SponsorshipPackagePolicy;
use App\Domain\Sponsorship\Policies\SponsorshipPolicy;
use App\Domain\Teams\Models\Team;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Policies\TicketOrderPolicy;
use App\Domain\Ticketing\Policies\TicketPolicy;
use App\Domain\Tournaments\Models\Dispute;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Policies\DisputePolicy;
use App\Domain\Tournaments\Policies\TournamentMatchPolicy;
use App\Domain\Tournaments\Policies\TournamentPolicy;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Policies\EventPolicy;
use App\Domain\Venues\Policies\VenuePolicy;
use App\Policies\ChatChannelPolicy;
use App\Policies\CommentPolicy;
use App\Policies\CommunityPolicy;
use App\Policies\ModerationPolicy;
use App\Policies\PostPolicy;
use App\Policies\ProfilePolicy;
use App\Policies\RecruitmentPostPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Community::class, CommunityPolicy::class);
        Gate::policy(RecruitmentPost::class, RecruitmentPostPolicy::class);
        Gate::policy(ChatChannel::class, ChatChannelPolicy::class);
        Gate::policy(Profile::class, ProfilePolicy::class);
        Gate::policy(ModerationCase::class, ModerationPolicy::class);
        Gate::policy(Tournament::class, TournamentPolicy::class);
        Gate::policy(TournamentMatch::class, TournamentMatchPolicy::class);
        Gate::policy(Dispute::class, DisputePolicy::class);
        Gate::policy(Venue::class, VenuePolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketOrder::class, TicketOrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Refund::class, RefundPolicy::class);
        Gate::policy(Payout::class, PayoutPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(Certificate::class, CertificatePolicy::class);
        Gate::policy(LearningPath::class, LearningPathPolicy::class);
        Gate::policy(AwarenessCampaign::class, AwarenessCampaignPolicy::class);

        Gate::policy(BrandProfile::class, BrandProfilePolicy::class);
        Gate::policy(SponsorshipPackage::class, SponsorshipPackagePolicy::class);
        Gate::policy(Sponsorship::class, SponsorshipPolicy::class);
        Gate::policy(SponsorshipDeliverable::class, SponsorshipDeliverablePolicy::class);

        Gate::define('view-platform-analytics', [AdminDashboardPolicy::class, 'viewPlatformAnalytics']);
        Gate::define('manage-feature-flags', [AdminDashboardPolicy::class, 'manageFeatureFlags']);
        Gate::define('view-system-health', [AdminDashboardPolicy::class, 'viewSystemHealth']);
        Gate::define('view-creator-analytics', [AdminDashboardPolicy::class, 'viewCreatorAnalytics']);
    }
}
