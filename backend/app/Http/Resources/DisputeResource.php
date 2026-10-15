<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'match_id' => $this->match_id,
            'tournament_id' => $this->match->tournament_id,
            'raised_by' => [
                'id' => $this->raisedBy->id,
                'name' => $this->raisedBy->name,
            ],
            'assigned_to' => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ] : null,
            'reason' => $this->reason,
            'status' => $this->status,
            'resolution_notes' => $this->resolution_notes,
            'evidence_urls' => $this->evidence_urls,
            'action_taken' => $this->action_taken,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
