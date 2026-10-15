<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rank' => $this->rank,
            'points' => $this->points,
            'rating' => $this->rating,
            'game' => [
                'id' => $this->game->id,
                'name' => $this->game->name,
                'slug' => $this->game->slug,
            ],
            'season' => $this->season ? [
                'id' => $this->season->id,
                'name' => $this->season->name,
            ] : null,
            'participant' => $this->team ? [
                'type' => 'team',
                'id' => $this->team->id,
                'name' => $this->team->name,
                'tag' => $this->team->tag,
                'logo_url' => $this->team->logo_url,
                'university' => $this->team->university,
            ] : [
                'type' => 'user',
                'id' => $this->user->id,
                'name' => $this->user->name,
                'handle' => $this->user->profile?->handle,
                'avatar_url' => $this->user->profile?->avatar_url,
                'university' => $this->user->profile?->university,
            ],
        ];
    }
}
