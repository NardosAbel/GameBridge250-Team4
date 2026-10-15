<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'event_id' => $this->event_id,
            'tournament_id' => $this->tournament_id,
            'total_amount' => (float) $this->total_amount,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'service_fee' => (float) $this->service_fee,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'billing_details' => $this->billing_details,
            'created_at' => $this->created_at?->toISOString(),
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'ticket_type_id' => $item->ticket_type_id,
                'ticket_type_name' => $item->ticketType?->name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
            ]),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
        ];
    }
}
