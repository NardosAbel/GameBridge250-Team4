<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTournamentAction
{
    /**
     * Create a new tournament for a verified organizer.
     */
    public function execute(User $organizer, array $data): Tournament
    {
        // Enforce organizer or super-admin role
        if (! $organizer->hasRole(['organizer', 'super-admin']) && ! $organizer->can('tournaments.create')) {
            throw new AuthorizationException('Only verified tournament organizers can create tournaments.');
        }

        return DB::transaction(function () use ($organizer, $data) {
            $slugBase = Str::slug($data['title']);
            $slug = $slugBase;
            $counter = 1;
            while (Tournament::where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$counter}";
                $counter++;
            }

            $tournament = Tournament::create([
                'title' => $data['title'],
                'slug' => $slug,
                'organizer_id' => $organizer->id,
                'game_id' => $data['game_id'],
                'platform' => $data['platform'] ?? 'Cross-platform',
                'region' => $data['region'] ?? 'Nationwide',
                'visibility' => $data['visibility'] ?? 'public',
                'banner_url' => $data['banner_url'] ?? null,
                'logo_url' => $data['logo_url'] ?? null,
                'theme_color' => $data['theme_color'] ?? '#10b981',
                'stream_url' => $data['stream_url'] ?? null,
                'venue_id' => $data['venue_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'format' => $data['format'] ?? 'single_elimination',
                'match_format' => $data['match_format'] ?? 'bo1',
                'event_type' => $data['event_type'] ?? 'online',
                'entry_type' => $data['entry_type'] ?? 'free',
                'entry_fee' => $data['entry_fee'] ?? 0,
                'currency' => $data['currency'] ?? 'ETB',
                'prize_pool' => $data['prize_pool'] ?? 0,
                'capacity' => $data['capacity'] ?? 16,
                'rules' => $data['rules'] ?? null,
                'eligibility_rules' => $data['eligibility_rules'] ?? [],
                'points_distribution' => $data['points_distribution'] ?? [
                    1 => 100,
                    2 => 60,
                    3 => 40,
                    4 => 20,
                ],
                'settings' => $data['settings'] ?? [
                    'third_place_match' => false,
                    'auto_advance_byes' => true,
                    'check_in_buffer_minutes' => 30,
                ],
                'registration_start_at' => $data['registration_start_at'] ?? now(),
                'registration_end_at' => $data['registration_end_at'] ?? now()->addDays(7),
                'check_in_start_at' => $data['check_in_start_at'] ?? null,
                'check_in_end_at' => $data['check_in_end_at'] ?? null,
                'starts_at' => $data['starts_at'] ?? now()->addDays(8),
                'ends_at' => $data['ends_at'] ?? null,
                'status' => $data['status'] ?? 'draft',
            ]);

            // Add creator as Head Admin staff
            TournamentStaff::create([
                'tournament_id' => $tournament->id,
                'user_id' => $organizer->id,
                'role' => 'admin',
            ]);

            AuditLogger::log(
                action: 'tournament.created',
                user: $organizer,
                entityType: 'tournament',
                entityId: $tournament->id,
                metadata: [
                    'title' => $tournament->title,
                    'format' => $tournament->format,
                ]
            );

            return $tournament;
        });
    }
}
