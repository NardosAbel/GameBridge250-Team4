<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Identity\Enums\UserStatusEnum;
use App\Domain\Moderation\Models\Report;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('user can submit a report against violating post', function () {
    $reporter = User::factory()->create();
    $offender = User::factory()->create();

    $post = Post::create([
        'user_id' => $offender->id,
        'content' => 'Inappropriate derogatory remarks violating campus conduct rules.',
        'visibility' => 'public',
    ]);

    Sanctum::actingAs($reporter);

    $res = $this->postJson('/api/v1/moderation/report', [
        'reportable_type' => 'post',
        'reportable_id' => $post->id,
        'reason' => 'Harassment and hate speech',
        'details' => 'Violates student esports code of conduct.',
    ]);

    $res->assertCreated()
        ->assertJsonPath('data.reason', 'Harassment and hate speech')
        ->assertJsonPath('data.reportable_type', 'Post');

    $this->assertDatabaseHas('reports', [
        'reporter_id' => $reporter->id,
        'reportable_id' => $post->id,
        'status' => 'pending',
    ]);
});

test('moderator can view queue and execute ban action, updating user status and logging audit', function () {
    $moderator = User::factory()->create();
    $moderator->assignRole(RoleEnum::MODERATOR->value);

    $reporter = User::factory()->create();
    $cheater = User::factory()->create();

    $report = Report::create([
        'reporter_id' => $reporter->id,
        'reportable_type' => User::class,
        'reportable_id' => $cheater->id,
        'reason' => 'Cheating in campus cup qualifier',
        'status' => 'pending',
    ]);

    // Regular player cannot view queue
    Sanctum::actingAs($reporter);
    $this->getJson('/api/v1/moderation/queue')->assertForbidden();

    // Moderator views queue
    Sanctum::actingAs($moderator);
    $queueRes = $this->getJson('/api/v1/moderation/queue');
    $queueRes->assertOk()
        ->assertJsonFragment(['reason' => 'Cheating in campus cup qualifier']);

    // Moderator bans cheating user
    $actionRes = $this->postJson('/api/v1/moderation/action', [
        'report_id' => $report->id,
        'target_user_id' => $cheater->id,
        'action' => 'ban',
        'reason' => 'Confirmed hardware cheat injection during qualifier tournament.',
    ]);

    $actionRes->assertOk();

    // Cheater status must be BANNED
    expect($cheater->fresh()->status)->toBe(UserStatusEnum::BANNED);

    // Report status updated
    expect($report->fresh()->status)->toBe('action_taken');

    // Audit log must be written
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $moderator->id,
        'action' => 'moderation.ban',
        'entity_type' => 'user',
        'entity_id' => $cheater->id,
    ]);
});
