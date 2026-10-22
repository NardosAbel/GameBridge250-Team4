<?php

namespace App\Domain\Profiles\Services;

use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;

class PlayerPassportService
{
    /**
     * Competitive record derived only from persisted rankings and registrations.
     *
     * @return array{
     *     tournament_count: int,
     *     verified_result_count: int,
     *     rating: int|null,
     *     rankings: list<array{game: string, points: int, rank: int|null}>,
     *     results: list<array{tournament_id: int, title: string, status: string, placement: int|null, points_awarded: int|null}>
     * }
     */
    public function forUser(User $user): array
    {
        $rankings = Ranking::query()
            ->where('user_id', $user->id)
            ->with('game:id,name')
            ->orderByDesc('points')
            ->limit(8)
            ->get();

        $registrations = TournamentRegistration::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'checked_in'])
            ->with('tournament:id,title,status')
            ->latest('id')
            ->limit(12)
            ->get();

        return [
            'tournament_count' => $registrations->count(),
            'verified_result_count' => $registrations->whereNotNull('placement')->count(),
            'rating' => $rankings->max('rating'),
            'rankings' => $rankings->map(fn (Ranking $ranking): array => [
                'game' => $ranking->game?->name ?? 'Unlisted game',
                'points' => (int) $ranking->points,
                'rank' => $ranking->rank,
            ])->values()->all(),
            'results' => $registrations->map(function (TournamentRegistration $registration): array {
                return [
                    'tournament_id' => (int) $registration->tournament_id,
                    'title' => $registration->tournament?->title ?? 'Tournament',
                    'status' => (string) $registration->status,
                    'placement' => $registration->placement,
                    'points_awarded' => $registration->points_awarded,
                ];
            })->values()->all(),
        ];
    }
}
