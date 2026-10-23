<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class CommunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'visibility' => $this->visibility,
            'avatar_url' => $this->avatar_url,
            'banner_url' => $this->banner_url,
            'creator' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'handle' => $this->creator->profile?->handle ?? $this->creator->profile?->gamer_tag,
            ] : null,
            'members_count' => $this->activeMembers()->count(),
            'is_member' => $user ? $this->members()->where('user_id', $user->id)->where('status', 'active')->exists() : false,
            'can_manage' => $user ? Gate::forUser($user)->allows('update', $this->resource) : false,
            'membership_status' => $user ? $this->members()->where('user_id', $user->id)->value('status') : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
