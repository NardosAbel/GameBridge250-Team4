<?php

namespace App\Http\Controllers\Web;

use App\Domain\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $tournaments = Tournament::query()
            ->with('game:id,name')
            ->whereIn('status', ['published', 'registration_open', 'check_in', 'live'])
            ->orderByRaw('starts_at is null')
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(fn (Tournament $tournament): array => [
                'id' => $tournament->id,
                'title' => $tournament->title,
                'game' => $tournament->game?->name ?? 'Campus esports',
                'format' => str_replace('_', ' ', (string) $tournament->format),
                'status' => str_replace('_', ' ', (string) $tournament->status),
                'date' => $tournament->starts_at?->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'recentTournaments' => $tournaments,
        ]);
    }
}
