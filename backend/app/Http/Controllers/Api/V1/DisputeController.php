<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tournaments\Actions\OpenDisputeAction;
use App\Domain\Tournaments\Actions\ResolveDisputeAction;
use App\Domain\Tournaments\Models\Dispute;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\OpenDisputeRequest;
use App\Http\Requests\Tournament\ResolveDisputeRequest;
use App\Http\Resources\DisputeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DisputeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Dispute::with(['match.tournament', 'raisedBy', 'assignedTo']);

        if ($request->filled('tournament_id')) {
            $tournamentId = $request->query('tournament_id');
            $query->whereHas('match', function ($q) use ($tournamentId): void {
                $q->where('tournament_id', $tournamentId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $disputes = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => DisputeResource::collection($disputes),
        ]);
    }

    public function store(OpenDisputeRequest $request, TournamentMatch $match, OpenDisputeAction $action): JsonResponse
    {
        $dispute = $action->execute($request->user(), $match, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Dispute raised successfully. Bracket advancement is paused pending official review.',
            'data' => new DisputeResource($dispute),
        ], 201);
    }

    public function show(Dispute $dispute): JsonResponse
    {
        Gate::authorize('view', $dispute);
        $dispute->load(['match.tournament', 'raisedBy', 'assignedTo']);

        return response()->json([
            'success' => true,
            'data' => new DisputeResource($dispute),
        ]);
    }

    public function resolve(ResolveDisputeRequest $request, Dispute $dispute, ResolveDisputeAction $action): JsonResponse
    {
        $resolved = $action->execute($request->user(), $dispute, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Dispute resolved successfully. Bracket updated.',
            'data' => new DisputeResource($resolved),
        ]);
    }
}
