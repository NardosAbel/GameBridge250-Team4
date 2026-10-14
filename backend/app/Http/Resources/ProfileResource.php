<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'gamer_tag' => $this->gamer_tag,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'cover_url' => $this->cover_url,
            'university' => $this->university,
            'school_id_number' => $this->school_id_number,
            'student_verified' => (bool) $this->student_verified,
            'platform_handles' => $this->platform_handles,
            'preferred_games' => $this->preferred_games,
            'is_public' => (bool) $this->is_public,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
