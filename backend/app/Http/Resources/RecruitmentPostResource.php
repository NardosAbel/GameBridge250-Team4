<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'rank_tier' => $this->rank_tier,
            'role' => $this->role,
            'location' => $this->location,
            'university' => $this->university,
            'age_group' => $this->age_group,
            'availability' => $this->availability,
            'platform' => $this->platform,
            'status' => $this->status,
            'author' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'handle' => $this->user?->profile?->handle ?? $this->user?->profile?->gamer_tag,
                'avatar_url' => $this->user?->profile?->avatar_url,
            ],
            'game' => $this->game ? [
                'id' => $this->game->id,
                'name' => $this->game->name,
                'slug' => $this->game->slug,
            ] : null,
            'team' => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'tag' => $this->team->tag,
                'logo_url' => $this->team->logo_url,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
