<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tournaments\Actions\AssignStaffAction;
use App\Domain\Tournaments\Actions\CheckInTournamentAction;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Actions\RegisterTournamentAction;
use App\Domain\Tournaments\Actions\UpdateSeedingAction;
use App\Domain\Tournaments\Actions\UpdateTournamentAction;
use App\Domain\Tournaments\Actions\UpdateTournamentStatusAction;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Services\StandingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\AssignStaffRequest;
use App\Http\Requests\Tournament\RegisterTournamentRequest;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateSeedingRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;
use App\Http\Resources\BracketResource;
use App\Http\Resources\TournamentRegistrationResource;
use App\Http\Resources\TournamentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tournament::query()->with(['organizer', 'game']);

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->query('game_id'));
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->query('platform'));
        }

        if ($request->filled('format')) {
            $query->where('format', $request->query('format'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%");
            });
        }

        $tournaments = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => TournamentResource::collection($tournaments),
            'meta' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'total' => $tournaments->total(),
            ],
        ]);
    }

    public function store(StoreTournamentRequest $request, CreateTournamentAction $action): JsonResponse
    {
        $tournament = $action->execute($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tournament created successfully.',
            'data' => new TournamentResource($tournament),
        ], 201);
    }

    public function show(Tournament $tournament, StandingsService $standingsService): JsonResponse
    {
        $tournament->load(['organizer', 'game', 'staff.user', 'brackets.nodes.match']);

        $standings = [];
        $firstBracket = $tournament->brackets->first();
        if ($firstBracket && in_array($firstBracket->type, ['round_robin', 'swiss', 'group_knockout'], true)) {
            $standings = $standingsService->getBracketStandings($firstBracket);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tournament' => new TournamentResource($tournament),
                'brackets' => BracketResource::collection($tournament->brackets),
                'standings' => $standings,
            ],
        ]);
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament, UpdateTournamentAction $action): JsonResponse
    {
        $updated = $action->execute($request->user(), $tournament, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tournament updated successfully.',
            'data' => new TournamentResource($updated),
        ]);
    }

    public function destroy(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorize('delete', $tournament);
        $tournament->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tournament deleted successfully.',
        ]);
    }

    public function updateStatus(Request $request, Tournament $tournament, UpdateTournamentStatusAction $action): JsonResponse
    {
        $this->authorize('update', $tournament);
        $request->validate(['status' => 'required|string']);

        $updated = $action->execute($request->user(), $tournament, $request->input('status'));

        return response()->json([
            'success' => true,
            'message' => 'Tournament status updated successfully.',
            'data' => new TournamentResource($updated),
        ]);
    }

    public function register(RegisterTournamentRequest $request, Tournament $tournament, RegisterTournamentAction $action): JsonResponse
    {
        $registration = $action->execute($request->user(), $tournament, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => new TournamentRegistrationResource($registration),
        ], 201);
    }

    public function participants(Tournament $tournament): JsonResponse
    {
        $registrations = $tournament->registrations()
            ->with(['user.profile', 'team'])
            ->orderByRaw('CASE WHEN seed IS NULL THEN 1 ELSE 0 END, seed ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TournamentRegistrationResource::collection($registrations),
        ]);
    }

    public function checkIn(Tournament $tournament, CheckInTournamentAction $action, Request $request): JsonResponse
    {
        $user = $request->user();
        $registration = $tournament->registrations()
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhereHas('team.members', function ($m) use ($user): void {
                        $m->where('user_id', $user->id);
                    });
            })
            ->firstOrFail();

        $updated = $action->execute($user, $registration);

        return response()->json([
            'success' => true,
            'message' => 'Checked in successfully.',
            'data' => new TournamentRegistrationResource($updated),
        ]);
    }

    public function updateSeeding(UpdateSeedingRequest $request, Tournament $tournament, UpdateSeedingAction $action): JsonResponse
    {
        $action->execute($request->user(), $tournament, $request->input('seeds'));

        return response()->json([
            'success' => true,
            'message' => 'Tournament seeds updated successfully.',
        ]);
    }

    public function generateBracket(Tournament $tournament, GenerateBracketAction $action, Request $request): JsonResponse
    {
        $bracket = $action->execute($request->user(), $tournament);

        return response()->json([
            'success' => true,
            'message' => 'Tournament bracket generated successfully and tournament is now live!',
            'data' => new BracketResource($bracket),
        ]);
    }

    public function assignStaff(AssignStaffRequest $request, Tournament $tournament, AssignStaffAction $action): JsonResponse
    {
        $staff = $action->execute(
            $request->user(),
            $tournament,
            (int) $request->input('user_id'),
            $request->input('role')
        );

        return response()->json([
            'success' => true,
            'message' => 'Tournament staff assigned successfully.',
            'data' => $staff,
        ]);
    }
}
