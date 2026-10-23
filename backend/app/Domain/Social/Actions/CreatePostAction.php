<?php

namespace App\Domain\Social\Actions;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Services\MediaUploadService;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CreatePostAction
{
    public function __construct(
        protected MediaUploadService $mediaUploadService
    ) {}

    public function execute(User $user, array $data, array $files = []): Post
    {
        if ($user->isBanned() || $user->isSuspended() || $user->isMuted()) {
            throw new InvalidArgumentException('Suspended or banned users cannot create posts.');
        }

        $this->assertCanPublish($user, $data);

        return DB::transaction(function () use ($user, $data, $files) {
            // Extract hashtags from content if present
            $hashtags = $data['hashtags'] ?? [];
            if (! empty($data['content'])) {
                preg_match_all('/#(\w+)/u', $data['content'], $matches);
                if (! empty($matches[1])) {
                    $hashtags = array_unique(array_merge($hashtags, $matches[1]));
                }
            }

            // Handle poll structure if poll
            $pollData = null;
            if (($data['type'] ?? 'text') === 'poll' && ! empty($data['poll_options'])) {
                $options = array_values(array_filter(array_map('trim', (array) $data['poll_options'])));
                if (count($options) < 2) {
                    throw new InvalidArgumentException('Polls must have at least 2 options.');
                }
                $pollData = [
                    'question' => $data['title'] ?? 'Poll',
                    'options' => array_map(fn ($opt) => ['text' => $opt, 'votes' => 0], $options),
                    'voters' => [], // user_id => option_index
                ];
            }

            $post = Post::create([
                'user_id' => $user->id,
                'community_id' => $data['community_id'] ?? null,
                'team_id' => $data['team_id'] ?? null,
                'game_id' => $data['game_id'] ?? null,
                'tournament_id' => $data['tournament_id'] ?? null,
                'title' => $data['title'] ?? null,
                'content' => $data['content'],
                'type' => $data['type'] ?? 'text',
                'location_tag' => $data['location_tag'] ?? null,
                'hashtags' => $hashtags,
                'poll_data' => $pollData,
                'visibility' => $data['visibility'] ?? 'public',
                'pinned' => false,
                'sponsored' => (bool) ($data['sponsored'] ?? false),
            ]);

            if (! empty($files)) {
                $this->mediaUploadService->uploadPostMedia($post, $files);
            }

            return $post->load(['media', 'user.profile', 'game', 'tournament', 'community', 'team']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCanPublish(User $user, array $data): void
    {
        $type = $data['type'] ?? 'text';
        $visibility = $data['visibility'] ?? 'public';

        if (! empty($data['sponsored']) && ! $user->hasAnyRole(['sponsor', 'super_admin'])) {
            throw new InvalidArgumentException('Only a sponsor account can mark a post as sponsored.');
        }

        if (! empty($data['tournament_id'])) {
            $tournament = Tournament::query()->find($data['tournament_id']);
            if ($tournament === null || ! Gate::forUser($user)->allows('view', $tournament)) {
                throw new InvalidArgumentException('That tournament is not available.');
            }

            if ($type === 'announcement' && ! Gate::forUser($user)->allows('update', $tournament)) {
                throw new InvalidArgumentException('Only an organizer of that tournament can publish an announcement.');
            }
        } elseif ($type === 'announcement') {
            throw new InvalidArgumentException('An announcement has to be linked to a tournament.');
        }

        if (! empty($data['team_id'])) {
            $team = Team::query()->find($data['team_id']);
            $belongs = $team && (
                $user->hasRole('super_admin')
                || (int) $team->leader_id === (int) $user->id
                || TeamMember::query()
                    ->where('team_id', $team->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->exists()
            );

            if (! $belongs) {
                throw new InvalidArgumentException('You can only post for a team you belong to.');
            }
        } elseif ($visibility === 'team') {
            throw new InvalidArgumentException('A team-only post needs a team.');
        }

        if (! empty($data['community_id'])) {
            $community = Community::query()->find($data['community_id']);
            $belongs = $community && (
                $user->hasAnyRole(['super_admin', 'moderator'])
                || (int) $community->creator_id === (int) $user->id
                || CommunityMember::query()
                    ->where('community_id', $community->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->exists()
            );

            if (! $belongs) {
                throw new InvalidArgumentException('Join this community before posting in it.');
            }
        } elseif ($visibility === 'community') {
            throw new InvalidArgumentException('A community-only post needs a community.');
        }
    }
}
