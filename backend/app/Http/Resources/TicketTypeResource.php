<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tournament_id' => $this->tournament_id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'quantity_total' => $this->quantity_total,
            'quantity_sold' => $this->quantity_sold,
            'remaining_quantity' => $this->remaining_quantity,
            'is_available' => $this->isAvailable(),
            'min_per_order' => $this->min_per_order,
            'max_per_order' => $this->max_per_order,
            'service_fee' => (float) $this->service_fee,
            'sales_start_at' => $this->sales_start_at?->toISOString(),
            'sales_end_at' => $this->sales_end_at?->toISOString(),
            'is_active' => $this->is_active,
        ];
    }
}
