<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'author' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'handle' => $this->user?->profile?->handle ?? $this->user?->profile?->gamer_tag,
                'avatar_url' => $this->user?->profile?->avatar_url,
                'is_verified' => $this->user?->profile?->is_verified ?? false,
            ],
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'visibility' => $this->visibility,
            'location_tag' => $this->location_tag,
            'hashtags' => $this->hashtags ?? [],
            'poll_data' => $this->poll_data,
            'user_voted_option' => ($user && ! empty($this->poll_data['voters'][$user->id])) ? $this->poll_data['voters'][$user->id] : null,
            'pinned' => (bool) $this->pinned,
            'sponsored' => (bool) $this->sponsored,
            'media' => $this->media->map(fn ($m) => [
                'id' => $m->id,
                'media_url' => $m->media_url,
                'media_type' => $m->media_type,
                'thumbnail_url' => $m->thumbnail_url,
            ]),
            'game' => $this->game ? [
                'id' => $this->game->id,
                'name' => $this->game->name,
                'slug' => $this->game->slug,
            ] : null,
            'community' => $this->community ? [
                'id' => $this->community->id,
                'name' => $this->community->name,
                'slug' => $this->community->slug,
            ] : null,
            'team' => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'tag' => $this->team->tag,
                'slug' => $this->team->slug,
            ] : null,
            'tournament' => $this->tournament ? [
                'id' => $this->tournament->id,
                'title' => $this->tournament->title,
            ] : null,
            'stats' => [
                'reactions_count' => $this->reactions_count ?? $this->reactions()->count(),
                'comments_count' => $this->comments_count ?? $this->comments()->count(),
                'shares_count' => $this->shares_count ?? 0,
            ],
            'user_reaction' => $this->viewerReaction($user),
            'is_bookmarked' => $this->viewerBookmarked($user),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function viewerReaction(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if ($this->relationLoaded('reactions')) {
            return $this->reactions->first()?->type;
        }

        return $this->reactions()->where('user_id', $user->id)->value('type');
    }

    private function viewerBookmarked(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->relationLoaded('bookmarks')) {
            return $this->bookmarks->isNotEmpty();
        }

        return $this->bookmarks()->where('user_id', $user->id)->exists();
    }
}
