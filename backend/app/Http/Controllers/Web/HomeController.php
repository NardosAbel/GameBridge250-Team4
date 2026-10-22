<?php

namespace App\Http\Controllers\Web;

use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Teams\Models\Team;
use App\Domain\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Landing', [
            'stats' => [
                'totalPlayers' => Profile::query()->count(),
                'totalTeams' => Team::query()->count(),
                'activeTournaments' => Tournament::query()
                    ->where('visibility', 'public')
                    ->whereIn('status', ['published', 'registration_open', 'check_in', 'live'])
                    ->count(),
                'partnerUniversities' => Profile::query()
                    ->whereNotNull('university')
                    ->where('university', '!=', '')
                    ->distinct()
                    ->count('university'),
            ],
            'games' => Game::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(8)
                ->get(['name', 'category', 'publisher']),
        ]);
    }
}
