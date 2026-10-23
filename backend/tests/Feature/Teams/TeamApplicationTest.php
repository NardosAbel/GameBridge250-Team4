<?php

use App\Domain\Teams\Actions\CreateTeamAction;
use App\Domain\Teams\Models\TeamInvitation;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;

test('a player applies once, a stranger cannot approve it, and the captain can', function () {
    $captain = User::factory()->create();
    $player = User::factory()->create();
    $stranger = User::factory()->create();

    $team = app(CreateTeamAction::class)->execute($captain, [
        'name' => 'Application Rifles',
        'tag' => 'APRF',
        'recruitment_status' => 'by_application',
    ]);

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/teams/{$team->id}/invite", [
            'user_id' => $player->id,
            'type' => 'join_request',
            'role' => 'owner',
            'message' => 'I play support.',
        ])
        ->assertCreated();

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/teams/{$team->id}/invite", [
            'user_id' => $player->id,
            'type' => 'join_request',
        ])
        ->assertStatus(422);

    expect(TeamInvitation::where('team_id', $team->id)->count())->toBe(1);
    $invitation = TeamInvitation::where('team_id', $team->id)->first();
    expect($invitation->role)->toBe('player')
        ->and($invitation->status)->toBe('pending');

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/teams/{$team->id}/invite", [
            'user_id' => $player->id,
            'type' => 'join_request',
        ])
        ->assertForbidden();

    $this->actingAs($player, 'sanctum')
        ->getJson("/api/v1/teams/{$team->id}")
        ->assertOk()
        ->assertJsonPath('data.viewer_request.status', 'pending')
        ->assertJsonPath('data.pending_requests', []);

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/team-invitations/{$invitation->id}/respond", [
            'response' => 'accepted',
        ])
        ->assertForbidden();

    $this->actingAs($captain, 'sanctum')
        ->getJson("/api/v1/teams/{$team->id}")
        ->assertOk()
        ->assertJsonPath('data.pending_requests.0.id', $invitation->id);

    $this->actingAs($captain, 'sanctum')
        ->postJson("/api/v1/team-invitations/{$invitation->id}/respond", [
            'response' => 'accepted',
        ])
        ->assertOk();

    expect(TeamMember::where('team_id', $team->id)->where('user_id', $player->id)->where('status', 'active')->exists())->toBeTrue();

    $closed = app(CreateTeamAction::class)->execute($captain, [
        'name' => 'Closed Roster',
        'tag' => 'CLSD',
        'recruitment_status' => 'closed',
    ]);

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/teams/{$closed->id}/invite", [
            'user_id' => $player->id,
            'type' => 'join_request',
        ])
        ->assertStatus(422);
});
