<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'tag' => $this->tag,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'game' => $this->game ? [
                'id' => $this->game->id,
                'name' => $this->game->name,
                'slug' => $this->game->slug,
            ] : null,
            'university' => $this->university,
            'location' => $this->location,
            'recruitment_status' => $this->recruitment_status,
            'achievements' => $this->achievements ?? [],
            'is_active' => (bool) $this->is_active,
            'leader' => $this->leader ? [
                'id' => $this->leader->id,
                'name' => $this->leader->name,
                'handle' => $this->leader->profile?->handle ?? $this->leader->profile?->gamer_tag,
                'avatar_url' => $this->leader->profile?->avatar_url,
            ] : null,
            'members' => ($this->relationLoaded('activeMembers') ? $this->activeMembers : $this->members)->map(fn ($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'name' => $m->user?->name,
                'handle' => $m->user?->profile?->handle ?? $m->user?->profile?->gamer_tag,
                'avatar_url' => $m->user?->profile?->avatar_url,
                'role' => $m->role,
                'status' => $m->status,
                'joined_at' => $m->joined_at?->toISOString(),
            ]),
            'members_count' => $this->activeMembers()->count(),
            'roster_history' => $this->rosterHistory->take(10)->map(fn ($h) => [
                'id' => $h->id,
                'action' => $h->action,
                'role' => $h->role,
                'user' => [
                    'id' => $h->user?->id,
                    'name' => $h->user?->name,
                ],
                'created_at' => $h->created_at?->toISOString(),
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
