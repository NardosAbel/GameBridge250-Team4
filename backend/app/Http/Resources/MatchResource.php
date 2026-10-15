<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canViewRoom = $user && $user->can('viewRoom', $this->resource);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tournament_id' => $this->tournament_id,
            'bracket_node_id' => $this->bracket_node_id,
            'match_format' => $this->match_format,
            'round' => $this->round,
            'match_number' => $this->match_number,
            'participant1_id' => $this->participant1_id,
            'participant2_id' => $this->participant2_id,
            'participant1_type' => $this->participant1_type,
            'participant2_type' => $this->participant2_type,
            'participant1_name' => $this->participant1_name,
            'participant2_name' => $this->participant2_name,
            'participant1_score' => $this->participant1_score,
            'participant2_score' => $this->participant2_score,
            'participant1_checked_in' => $this->participant1_checked_in,
            'participant2_checked_in' => $this->participant2_checked_in,
            'participant1_ready' => $this->participant1_ready,
            'participant2_ready' => $this->participant2_ready,
            'winner_id' => $this->winner_id,
            'status' => $this->status,
            'is_bye' => $this->is_bye,
            // Restricted Room Credentials
            'room_id' => $canViewRoom ? $this->room_id : null,
            'room_password' => $canViewRoom ? $this->room_password : null,
            'room_server' => $canViewRoom ? $this->room_server : null,
            'room_instructions' => $canViewRoom ? $this->room_instructions : null,
            'room' => $canViewRoom ? [
                'room_id' => $this->room_id,
                'room_password' => $this->room_password,
                'room_server' => $this->room_server,
                'room_instructions' => $this->room_instructions,
            ] : null,
            'referee' => $this->referee ? [
                'id' => $this->referee->id,
                'name' => $this->referee->name,
            ] : null,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'latest_result' => $this->latestResult ? [
                'id' => $this->latestResult->id,
                'submitted_by_id' => $this->latestResult->submitted_by_id,
                'confirmed_by_id' => $this->latestResult->confirmed_by_id,
                'participant1_score' => $this->latestResult->participant1_score,
                'participant2_score' => $this->latestResult->participant2_score,
                'status' => $this->latestResult->status,
                'notes' => $this->latestResult->notes,
                'evidence' => $this->latestResult->evidence->map(fn ($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'url' => $e->url,
                    'description' => $e->description,
                ]),
            ] : null,
            'active_dispute' => $this->activeDispute ? [
                'id' => $this->activeDispute->id,
                'raised_by_id' => $this->activeDispute->raised_by_id,
                'reason' => $this->activeDispute->reason,
                'status' => $this->activeDispute->status,
            ] : null,
        ];
    }
}
