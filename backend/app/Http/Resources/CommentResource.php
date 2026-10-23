<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'content' => $this->content,
            'created_at' => $this->created_at?->toISOString(),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'handle' => $this->user->profile?->handle ?? $this->user->profile?->gamer_tag,
                'avatar_url' => $this->user->profile?->avatar_url,
            ] : null,
            'replies' => $this->whenLoaded('replies', fn () => CommentResource::collection($this->replies)),
        ];
    }
}
