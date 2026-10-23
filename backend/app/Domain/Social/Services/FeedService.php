<?php

namespace App\Domain\Social\Services;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Social\Models\Follow;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\UserBlock;
use App\Domain\Social\Models\UserMute;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FeedService
{
    /**
     * Get feed posts (personalized or explore).
     */
    public function getFeed(?User $user = null, string $type = 'personalized', array $filters = []): LengthAwarePaginator
    {
        $query = Post::query()
            ->with([
                'user.profile',
                'media',
                'game',
                'community',
                'team',
                'tournament',
                'reactions' => function ($reactionQuery) use ($user) {
                    if ($user) {
                        $reactionQuery->where('user_id', $user->id);
                    } else {
                        $reactionQuery->whereRaw('1 = 0');
                    }
                },
                'bookmarks' => function ($bookmarkQuery) use ($user) {
                    if ($user) {
                        $bookmarkQuery->where('user_id', $user->id);
                    } else {
                        $bookmarkQuery->whereRaw('1 = 0');
                    }
                },
            ])
            ->withCount(['comments', 'reactions', 'shares']);

        // Exclude blocked and muted users if user is authenticated
        if ($user) {
            $blockedUserIds = UserBlock::where('user_id', $user->id)->pluck('blocked_user_id')
                ->merge(UserBlock::where('blocked_user_id', $user->id)->pluck('user_id'))
                ->all();

            $mutedUserIds = UserMute::where('user_id', $user->id)->pluck('muted_user_id')->all();

            $excludedUserIds = array_unique(array_merge($blockedUserIds, $mutedUserIds));

            if (! empty($excludedUserIds)) {
                $query->whereNotIn('user_id', $excludedUserIds);
            }
        }

        // Apply game filter if requested
        if (! empty($filters['game_id'])) {
            $query->where('game_id', $filters['game_id']);
        }

        // Apply community filter if requested
        if (! empty($filters['community_id'])) {
            $query->where('community_id', $filters['community_id']);
        }

        // Apply team filter if requested
        if (! empty($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['tournament_id'])) {
            $query->where('tournament_id', $filters['tournament_id']);
        }

        // Apply hashtag filter if requested
        if (! empty($filters['hashtag'])) {
            $tag = ltrim($filters['hashtag'], '#');
            $query->whereJsonContains('hashtags', $tag);
        }

        if ($user && $type === 'personalized') {
            $followedUserIds = Follow::where('follower_id', $user->id)
                ->where('followable_type', User::class)
                ->pluck('followable_id')
                ->all();

            $joinedCommunityIds = CommunityMember::where('user_id', $user->id)
                ->where('status', 'active')
                ->pluck('community_id')
                ->all();

            $joinedTeamIds = TeamMember::where('user_id', $user->id)
                ->where('status', 'active')
                ->pluck('team_id')
                ->all();

            $preferredGameIds = $user->profile?->preferred_games ?? [];
            $userLocation = $user->profile?->location;

            // Personalized algorithm: followed users, joined communities/teams, preferred games, location, or user's own posts
            $query->where(function (Builder $q) use ($user, $followedUserIds, $joinedCommunityIds, $joinedTeamIds, $preferredGameIds, $userLocation) {
                $q->where('user_id', $user->id)
                    ->orWhereIn('user_id', $followedUserIds)
                    ->orWhereIn('community_id', $joinedCommunityIds)
                    ->orWhereIn('team_id', $joinedTeamIds);

                if (! empty($preferredGameIds)) {
                    $q->orWhereIn('game_id', $preferredGameIds);
                }

                if (! empty($userLocation)) {
                    $q->orWhere('location_tag', 'like', "%{$userLocation}%");
                }

                // If user doesn't follow much yet, include public posts
                $q->orWhere('visibility', 'public');
            });
        } else {
            // Explore feed: only public posts
            $query->where('visibility', 'public');
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
