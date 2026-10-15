<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tournaments\Actions\ConfirmMatchResultAction;
use App\Domain\Tournaments\Actions\MatchCheckInAction;
use App\Domain\Tournaments\Actions\OverrideMatchResultAction;
use App\Domain\Tournaments\Actions\SubmitMatchResultAction;
use App\Domain\Tournaments\Actions\UpdateMatchRoomAction;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\OverrideMatchResultRequest;
use App\Http\Requests\Tournament\SubmitMatchResultRequest;
use App\Http\Requests\Tournament\UpdateMatchRoomRequest;
use App\Http\Resources\MatchResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function show(TournamentMatch $match): JsonResponse
    {
        $match->load(['tournament', 'bracketNode', 'referee', 'latestResult.evidence', 'activeDispute']);

        return response()->json([
            'success' => true,
            'data' => new MatchResource($match),
        ]);
    }

    public function checkIn(TournamentMatch $match, MatchCheckInAction $action, Request $request): JsonResponse
    {
        $updated = $action->execute($request->user(), $match);

        return response()->json([
            'success' => true,
            'message' => 'Match ready-up confirmed.',
            'data' => new MatchResource($updated),
        ]);
    }

    public function updateRoom(UpdateMatchRoomRequest $request, TournamentMatch $match, UpdateMatchRoomAction $action): JsonResponse
    {
        $updated = $action->execute($request->user(), $match, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Match room credentials updated.',
            'data' => new MatchResource($updated),
        ]);
    }

    public function submitResult(SubmitMatchResultRequest $request, TournamentMatch $match, SubmitMatchResultAction $action): JsonResponse
    {
        $result = $action->execute($request->user(), $match, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Match result submitted successfully. Awaiting opponent confirmation.',
            'data' => [
                'result_id' => $result->id,
                'status' => $result->status,
                'match' => new MatchResource($match->fresh()),
            ],
        ]);
    }

    public function confirmResult(TournamentMatch $match, ConfirmMatchResultAction $action, Request $request): JsonResponse
    {
        $updated = $action->execute($request->user(), $match);

        return response()->json([
            'success' => true,
            'message' => 'Match result confirmed. Winner advanced.',
            'data' => new MatchResource($updated),
        ]);
    }

    public function overrideResult(OverrideMatchResultRequest $request, TournamentMatch $match, OverrideMatchResultAction $action): JsonResponse
    {
        $updated = $action->execute($request->user(), $match, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Match result overridden by tournament official. Bracket recalculated.',
            'data' => new MatchResource($updated),
        ]);
    }
}
