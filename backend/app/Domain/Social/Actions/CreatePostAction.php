<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Post;
use App\Domain\Social\Services\MediaUploadService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
                'title' => $data['title'] ?? null,
                'content' => $data['content'],
                'type' => $data['type'] ?? 'text',
                'location_tag' => $data['location_tag'] ?? null,
                'hashtags' => $hashtags,
                'poll_data' => $pollData,
                'visibility' => $data['visibility'] ?? 'public',
                'pinned' => (bool) ($data['pinned'] ?? false),
            ]);

            if (! empty($files)) {
                $this->mediaUploadService->uploadPostMedia($post, $files);
            }

            return $post->load(['media', 'user.profile', 'game']);
        });
    }
}
