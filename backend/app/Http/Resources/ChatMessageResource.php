<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'channel_id' => $this->channel_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'handle' => $this->user?->profile?->handle ?? $this->user?->profile?->gamer_tag,
                'avatar_url' => $this->user?->profile?->avatar_url,
            ],
            'message' => $this->message,
            'mentions' => $this->mentions ?? [],
            'attachment_url' => $this->attachment_url,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
