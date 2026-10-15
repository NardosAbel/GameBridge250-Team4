<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'platform' => $this->platform,
            'region' => $this->region,
            'visibility' => $this->visibility,
            'banner_url' => $this->banner_url,
            'logo_url' => $this->logo_url,
            'theme_color' => $this->theme_color,
            'stream_url' => $this->stream_url,
            'format' => $this->format,
            'match_format' => $this->match_format,
            'event_type' => $this->event_type,
            'entry_type' => $this->entry_type,
            'entry_fee' => (float) $this->entry_fee,
            'currency' => $this->currency,
            'prize_pool' => (float) $this->prize_pool,
            'capacity' => $this->capacity,
            'rules' => $this->rules,
            'eligibility_rules' => $this->eligibility_rules,
            'points_distribution' => $this->points_distribution,
            'settings' => $this->settings,
            'status' => $this->status,
            'registration_start_at' => $this->registration_start_at?->toISOString(),
            'registration_end_at' => $this->registration_end_at?->toISOString(),
            'check_in_start_at' => $this->check_in_start_at?->toISOString(),
            'check_in_end_at' => $this->check_in_end_at?->toISOString(),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'organizer' => $this->organizer ? [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
            ] : null,
            'game' => $this->game ? [
                'id' => $this->game->id,
                'name' => $this->game->name,
                'slug' => $this->game->slug,
                'category' => $this->game->category,
                'banner_url' => $this->game->banner_url,
                'icon_url' => $this->game->icon_url,
            ] : null,
            'participants_count' => $this->approvedRegistrations()->count(),
            'staff' => $this->staff->map(fn ($s) => [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'name' => $s->user?->name,
                'role' => $s->role,
            ]),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
