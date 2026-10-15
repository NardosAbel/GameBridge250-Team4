<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BracketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'stage' => $this->stage,
            'type' => $this->type,
            'status' => $this->status,
            'settings' => $this->settings,
            'nodes' => $this->nodes->map(fn ($n) => [
                'id' => $n->id,
                'group_number' => $n->group_number,
                'round' => $n->round,
                'node_order' => $n->node_order,
                'next_node_id' => $n->next_node_id,
                'loser_next_node_id' => $n->loser_next_node_id,
                'is_bye' => $n->is_bye,
                'match' => $n->match ? new MatchResource($n->match) : null,
            ]),
        ];
    }
}
