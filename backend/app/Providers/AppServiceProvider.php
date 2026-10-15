<?php

namespace App\Providers;

use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Communities\Models\Community;
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
    }
}
