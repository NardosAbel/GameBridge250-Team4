<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\RankingSnapshot;
use App\Domain\Tournaments\Models\Season;
use App\Domain\Tournaments\Services\RankingCalculationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\RankingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ranking::with(['game', 'season', 'user.profile', 'team']);

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->query('game_id'));
        }

        if ($request->filled('season_id')) {
            $query->where('season_id', $request->query('season_id'));
        }

        $rankings = $query->orderBy('rank', 'asc')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => RankingResource::collection($rankings),
            'meta' => [
                'current_page' => $rankings->currentPage(),
                'last_page' => $rankings->lastPage(),
                'total' => $rankings->total(),
            ],
        ]);
    }

    public function leaderboards(Request $request): JsonResponse
    {
        $currentSeason = Season::where('is_current', true)->first();
        $seasonId = $request->query('season_id', $currentSeason?->id);

        $query = Ranking::with(['game', 'season', 'user.profile', 'team'])
            ->whereNotNull('rank')
            ->orderBy('rank', 'asc');

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->query('game_id'));
        }

        if ($seasonId) {
            $query->where('season_id', $seasonId);
        }

        $top = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => RankingResource::collection($top),
        ]);
    }

    public function snapshot(Request $request, RankingCalculationService $service): JsonResponse
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'season_id' => 'nullable|exists:seasons,id',
            'type' => 'nullable|in:global,seasonal,university',
        ]);

        $snapshot = $service->captureSnapshot(
            (int) $request->input('game_id'),
            $request->input('season_id') ? (int) $request->input('season_id') : null,
            $request->input('type', 'global')
        );

        return response()->json([
            'success' => true,
            'message' => 'Ranking snapshot captured successfully.',
            'data' => $snapshot,
        ], 201);
    }

    public function snapshots(Request $request): JsonResponse
    {
        $query = RankingSnapshot::with(['game', 'season']);

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->query('game_id'));
        }

        $snapshots = $query->latest('snapshot_date')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $snapshots,
        ]);
    }
}
