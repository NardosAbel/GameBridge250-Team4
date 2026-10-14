<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reporter' => [
                'id' => $this->reporter?->id,
                'name' => $this->reporter?->name,
                'handle' => $this->reporter?->profile?->handle ?? $this->reporter?->profile?->gamer_tag,
            ],
            'reportable_type' => class_basename($this->reportable_type),
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason,
            'details' => $this->details,
            'status' => $this->status,
            'reviewer' => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
