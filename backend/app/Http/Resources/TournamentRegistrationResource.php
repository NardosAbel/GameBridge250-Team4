<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tournament_id' => $this->tournament_id,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'participant_name' => $this->participant_name,
            'status' => $this->status,
            'checked_in' => $this->checked_in,
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'seed' => $this->seed,
            'placement' => $this->placement,
            'points_awarded' => $this->points_awarded,
            'roster_snapshot' => $this->roster_snapshot,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'handle' => $this->user->profile?->handle,
                'gamer_tag' => $this->user->profile?->gamer_tag,
                'avatar_url' => $this->user->profile?->avatar_url,
                'university' => $this->user->profile?->university,
            ] : null,
            'team' => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'tag' => $this->team->tag,
                'logo_url' => $this->team->logo_url,
                'university' => $this->team->university,
            ] : null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
