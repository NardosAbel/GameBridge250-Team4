<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;

class UpdateTournamentAction
{
    public function execute(User $actor, Tournament $tournament, array $data): Tournament
    {
        $old = $tournament->only(['title', 'format', 'match_format', 'status', 'capacity', 'prize_pool']);

        $tournament->update(array_filter([
            'title' => $data['title'] ?? $tournament->title,
            'platform' => $data['platform'] ?? $tournament->platform,
            'region' => $data['region'] ?? $tournament->region,
            'visibility' => $data['visibility'] ?? $tournament->visibility,
            'banner_url' => $data['banner_url'] ?? $tournament->banner_url,
            'logo_url' => $data['logo_url'] ?? $tournament->logo_url,
            'theme_color' => $data['theme_color'] ?? $tournament->theme_color,
            'stream_url' => $data['stream_url'] ?? $tournament->stream_url,
            'venue_id' => array_key_exists('venue_id', $data) ? $data['venue_id'] : $tournament->venue_id,
            'format' => $data['format'] ?? $tournament->format,
            'match_format' => $data['match_format'] ?? $tournament->match_format,
            'event_type' => $data['event_type'] ?? $tournament->event_type,
            'entry_type' => $data['entry_type'] ?? $tournament->entry_type,
            'entry_fee' => $data['entry_fee'] ?? $tournament->entry_fee,
            'currency' => $data['currency'] ?? $tournament->currency,
            'prize_pool' => $data['prize_pool'] ?? $tournament->prize_pool,
            'capacity' => $data['capacity'] ?? $tournament->capacity,
            'rules' => $data['rules'] ?? $tournament->rules,
            'eligibility_rules' => $data['eligibility_rules'] ?? $tournament->eligibility_rules,
            'points_distribution' => $data['points_distribution'] ?? $tournament->points_distribution,
            'settings' => $data['settings'] ?? $tournament->settings,
            'registration_start_at' => $data['registration_start_at'] ?? $tournament->registration_start_at,
            'registration_end_at' => $data['registration_end_at'] ?? $tournament->registration_end_at,
            'check_in_start_at' => $data['check_in_start_at'] ?? $tournament->check_in_start_at,
            'check_in_end_at' => $data['check_in_end_at'] ?? $tournament->check_in_end_at,
            'starts_at' => $data['starts_at'] ?? $tournament->starts_at,
            'ends_at' => $data['ends_at'] ?? $tournament->ends_at,
        ], fn ($val) => $val !== null));

        AuditLogger::log(
            action: 'tournament.updated',
            user: $actor,
            entityType: 'tournament',
            entityId: $tournament->id,
            oldValues: $old,
            newValues: $tournament->only(array_keys($old))
        );

        return $tournament;
    }
}
