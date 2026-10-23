<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tournaments\Actions\AssignStaffAction;
use App\Domain\Tournaments\Actions\CheckInTournamentAction;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Actions\RegisterTournamentAction;
use App\Domain\Tournaments\Actions\ReviewTournamentRegistrationAction;
use App\Domain\Tournaments\Actions\UpdateSeedingAction;
use App\Domain\Tournaments\Actions\UpdateTournamentAction;
use App\Domain\Tournaments\Actions\UpdateTournamentStatusAction;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
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
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TournamentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tournament::query()->with(['organizer', 'game', 'staff.user']);

        $viewer = $this->viewer($request);
        if (! $viewer?->hasRole('super_admin')) {
            $query->where(function ($visibility) use ($viewer): void {
                $visibility->where(function ($public): void {
                    $public->where('visibility', 'public')
                        ->whereNotIn('status', ['draft', 'archived']);
                });

                if ($viewer !== null) {
                    $visibility->orWhere('organizer_id', $viewer->id)
                        ->orWhereHas('staff', fn ($staff) => $staff->where('user_id', $viewer->id))
                        ->orWhereHas('registrations', fn ($registrations) => $registrations->where('user_id', $viewer->id));
                }
            });
        }

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

    public function show(Request $request, Tournament $tournament, StandingsService $standingsService, RegisterTournamentAction $registrations): JsonResponse
    {
        $this->authorizeTournamentView($request, $tournament);

        $tournament->load(['organizer', 'game', 'staff.user', 'brackets.nodes.match']);

        $standings = [];
        $firstBracket = $tournament->brackets->first();
        if ($firstBracket && in_array($firstBracket->type, ['round_robin', 'swiss', 'group_knockout'], true)) {
            $standings = $standingsService->getBracketStandings($firstBracket);
        }

        $payload = (new TournamentResource($tournament))->resolve($request);
        $viewer = $this->viewer($request);
        $registration = $viewer
            ? $tournament->registrations()->where('user_id', $viewer->id)->first(['id', 'status'])
            : null;
        $payload['viewer_registration'] = $registration
            ? ['status' => $registration->status]
            : null;
        $payload['viewer_eligibility'] = $viewer
            ? $registrations->eligibilityIssue($viewer, $tournament)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'tournament' => $payload,
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
        Gate::authorize('delete', $tournament);
        $tournament->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tournament deleted successfully.',
        ]);
    }

    public function updateStatus(Request $request, Tournament $tournament, UpdateTournamentStatusAction $action): JsonResponse
    {
        Gate::authorize('update', $tournament);
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
            'message' => $registration->status === 'approved'
                ? 'Registration approved.'
                : 'Application submitted. An organizer still has to approve it.',
            'data' => new TournamentRegistrationResource($registration),
        ], 201);
    }

    public function participants(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorizeTournamentView($request, $tournament);

        $registrations = $tournament->registrations()
            ->with(['user.profile', 'team'])
            ->when(Gate::forUser($request->user())->denies('update', $tournament), function ($query) {
                $query->where('status', 'approved');
            })
            ->orderByRaw('CASE WHEN seed IS NULL THEN 1 ELSE 0 END, seed ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TournamentRegistrationResource::collection($registrations),
        ]);
    }

    public function reviewRegistration(
        Request $request,
        Tournament $tournament,
        TournamentRegistration $registration,
        ReviewTournamentRegistrationAction $action
    ): JsonResponse {
        if ($registration->tournament_id !== $tournament->id) {
            abort(404);
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
        ]);

        $updated = $action->execute($request->user(), $registration, $validated['decision']);

        return response()->json([
            'success' => true,
            'message' => $updated->status === 'approved' ? 'Application approved.' : 'Application rejected.',
            'data' => new TournamentRegistrationResource($updated),
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

    private function viewer(Request $request): ?User
    {
        $user = $request->user() ?? $request->user('sanctum');

        return $user instanceof User ? $user : null;
    }

    private function authorizeTournamentView(Request $request, Tournament $tournament): void
    {
        $viewer = $this->viewer($request);

        if ($viewer instanceof User) {
            Gate::forUser($viewer)->authorize('view', $tournament);

            return;
        }

        Gate::authorize('view', $tournament);
    }
}
