<?php

namespace App\Domain\Administration\Services;

use App\Domain\Social\Models\Follow;
use App\Domain\Social\Models\Post;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Models\User;

class CreatorAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function getCreatorInsights(User $creator): array
    {
        // 1. Post & Content Reach
        $posts = Post::where('user_id', $creator->id)->get();
        $totalPosts = $posts->count();
        $totalLikes = $posts->sum('likes_count');
        $totalComments = $posts->sum('comments_count');
        $totalShares = $posts->sum('shares_count');

        $totalEngagements = $totalLikes + $totalComments + $totalShares;
        $avgEngagementPerPost = $totalPosts > 0 ? round($totalEngagements / $totalPosts, 1) : 0.0;

        // 2. Follower Analytics
        $totalFollowers = Follow::where('followable_type', 'user')
            ->where('followable_id', $creator->id)
            ->count();

        // 3. Sponsored Opportunities for Creators
        $availableSponsorships = SponsorshipPackage::whereIn('target_type', ['creator', 'player', 'platform'])
            ->where('is_published', true)
            ->latest()
            ->limit(5)
            ->get();

        return [
            'creator_id' => $creator->id,
            'creator_name' => $creator->name,
            'gamer_tag' => $creator->profile?->gamer_tag ?? $creator->name,
            'content_metrics' => [
                'total_posts' => $totalPosts,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'total_shares' => $totalShares,
                'total_engagements' => $totalEngagements,
                'avg_engagement_per_post' => $avgEngagementPerPost,
            ],
            'audience_insights' => [
                'total_followers' => $totalFollowers,
                'primary_university' => $creator->profile?->university ?? 'Campus Esports Network',
                'preferred_games' => $creator->profile?->preferred_games ?? ['EA Sports FC 25', 'PUBG Mobile'],
            ],
            'sponsored_opportunities' => $availableSponsorships,
        ];
    }
}
