<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'payment_id' => $this->payment_id,
            'ticket_order_id' => $this->ticket_order_id,
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'reason_category' => $this->reason_category,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'requested_by' => [
                'id' => $this->requestedBy?->id,
                'name' => $this->requestedBy?->name,
            ],
            'processed_by' => $this->processedBy ? [
                'id' => $this->processedBy->id,
                'name' => $this->processedBy->name,
            ] : null,
        ];
    }
}
