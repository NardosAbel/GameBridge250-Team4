<?php

namespace App\Providers;

use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Communities\Models\Community;
use App\Domain\Moderation\Models\ModerationCase;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Recruitment\Models\RecruitmentPost;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Models\Post;
use App\Domain\Teams\Models\Team;
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
    }
}
