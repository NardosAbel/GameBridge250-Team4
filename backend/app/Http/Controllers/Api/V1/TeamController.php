<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Teams\Actions\CreateTeamAction;
use App\Domain\Teams\Actions\InviteOrRequestTeamAction;
use App\Domain\Teams\Actions\RemoveTeamMemberAction;
use App\Domain\Teams\Actions\RespondTeamInvitationAction;
use App\Domain\Teams\Actions\TransferTeamOwnershipAction;
use App\Domain\Teams\Actions\UpdateTeamAction;
use App\Domain\Teams\Actions\UpdateTeamMemberRoleAction;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamInvitation;
use App\Domain\Teams\Models\TeamMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\TeamInviteRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Team::query()
            ->with(['leader.profile', 'game'])
            ->withCount('activeMembers')
            ->where('is_active', true);

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->query('game_id'));
        }

        if ($request->filled('university')) {
            $query->where('university', 'like', "%{$request->query('university')}%");
        }

        if ($request->filled('recruitment_status')) {
            $query->where('recruitment_status', $request->query('recruitment_status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tag', 'like', "%{$search}%");
            });
        }

        $teams = $query->latest()->paginate(15);

        return response()->json([
            'data' => TeamResource::collection($teams),
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'total' => $teams->total(),
            ],
        ]);
    }

    public function store(StoreTeamRequest $request, CreateTeamAction $action): JsonResponse
    {
        Gate::authorize('create', Team::class);

        $team = $action->execute(
            user: $request->user(),
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Team created successfully.',
            'data' => new TeamResource($team),
        ], 201);
    }

    public function show(Team $team): JsonResponse
    {
        Gate::authorize('view', $team);

        $team->load(['leader.profile', 'activeMembers.user.profile', 'game', 'rosterHistory.user']);

        return response()->json([
            'data' => new TeamResource($team),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team, UpdateTeamAction $action): JsonResponse
    {
        Gate::authorize('update', $team);

        $updatedTeam = $action->execute($team, $request->validated());

        return response()->json([
            'message' => 'Team updated successfully.',
            'data' => new TeamResource($updatedTeam),
        ]);
    }

    public function invite(TeamInviteRequest $request, Team $team, InviteOrRequestTeamAction $action): JsonResponse
    {
        $type = $request->validated('type', 'invitation');
        $targetUser = User::findOrFail($request->validated('user_id'));

        if ($type === 'invitation') {
            Gate::authorize('invite', $team);
        }

        $invitation = $action->execute(
            actor: $request->user(),
            team: $team,
            targetUser: $targetUser,
            type: $type,
            role: $request->validated('role', 'player'),
            message: $request->validated('message')
        );

        return response()->json([
            'message' => $type === 'invitation' ? 'Invitation sent successfully.' : 'Join request submitted successfully.',
            'data' => $invitation,
        ], 201);
    }

    public function respondInvitation(Request $request, TeamInvitation $invitation, RespondTeamInvitationAction $action): JsonResponse
    {
        $validated = $request->validate([
            'response' => ['required', 'string', 'in:accepted,declined'],
        ]);

        $resolved = $action->execute(
            actor: $request->user(),
            invitation: $invitation,
            response: $validated['response']
        );

        return response()->json([
            'message' => "Invitation {$validated['response']}.",
            'data' => $resolved,
        ]);
    }

    public function updateMemberRole(Request $request, TeamMember $member, UpdateTeamMemberRoleAction $action): JsonResponse
    {
        Gate::authorize('manageRoster', $member->team);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:owner,captain,coach,manager,player,substitute'],
        ]);

        $updated = $action->execute(
            actor: $request->user(),
            member: $member,
            newRole: $validated['role']
        );

        return response()->json([
            'message' => 'Member role updated successfully.',
            'data' => $updated,
        ]);
    }

    public function removeMember(Request $request, TeamMember $member, RemoveTeamMemberAction $action): JsonResponse
    {
        // Allowed if user is kicking (manageRoster) OR user is voluntarily leaving
        if ($request->user()->id !== $member->user_id) {
            Gate::authorize('manageRoster', $member->team);
        }

        $action->execute(
            actor: $request->user(),
            member: $member
        );

        return response()->json([
            'message' => 'Member removed successfully.',
        ]);
    }

    public function transferOwnership(Request $request, Team $team, TransferTeamOwnershipAction $action): JsonResponse
    {
        Gate::authorize('transferOwnership', $team);

        $validated = $request->validate([
            'new_owner_id' => ['required', 'exists:users,id'],
        ]);

        $newOwner = User::findOrFail($validated['new_owner_id']);
        $updatedTeam = $action->execute(
            currentOwner: $request->user(),
            team: $team,
            newOwner: $newOwner
        );

        return response()->json([
            'message' => 'Ownership transferred successfully.',
            'data' => new TeamResource($updatedTeam),
        ]);
    }
}
