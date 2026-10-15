<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'ticket_code' => $this->ticket_code,
            'status' => $this->status,
            'ticket_type' => $this->ticketType ? [
                'id' => $this->ticketType->id,
                'name' => $this->ticketType->name,
                'category' => $this->ticketType->category,
                'price' => (float) $this->ticketType->price,
            ] : null,
            'event' => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'start_time' => $this->event->start_time?->toISOString(),
                'venue_name' => $this->event->venue?->name,
            ] : null,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'gamer_tag' => $this->user?->profile?->gamer_tag,
            ],
            // Signed QR payload for scanner (Only contains ref and signature)
            'qr_data' => [
                'ref' => $this->uuid,
                'sig' => $this->qr_signature,
            ],
            'qr_string' => json_encode([
                'ref' => $this->uuid,
                'sig' => $this->qr_signature,
            ]),
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
