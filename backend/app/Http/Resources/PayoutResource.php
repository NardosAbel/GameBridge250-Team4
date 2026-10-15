<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'event_id' => $this->event_id,
            'tournament_id' => $this->tournament_id,
            'amount' => (float) $this->amount,
            'gross_amount' => (float) $this->gross_amount,
            'platform_fee' => (float) $this->platform_fee,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'account_number' => $this->account_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
