<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Enums\UserStatusEnum;
use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Moderation\Models\ModerationCase;
use App\Domain\Moderation\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TakeModerationAction
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function execute(
        User $moderator,
        User $targetUser,
        string $action, // warning, mute, suspend, ban, delete_content, dismiss
        string $reason,
        ?Report $report = null,
        ?Model $contentToDelete = null,
        ?Carbon $expiresAt = null
    ): ModerationCase {
        $validActions = ['warning', 'mute', 'suspend', 'ban', 'delete_content', 'dismiss'];
        if (! in_array($action, $validActions, true)) {
            throw new InvalidArgumentException("Invalid moderation action: {$action}");
        }

        return DB::transaction(function () use ($moderator, $targetUser, $action, $reason, $report, $contentToDelete, $expiresAt) {
            // Apply impact to user or content
            if ($action === 'ban') {
                $targetUser->update(['status' => UserStatusEnum::BANNED]);
            } elseif ($action === 'suspend') {
                $targetUser->update(['status' => UserStatusEnum::SUSPENDED]);
            } elseif ($action === 'delete_content' && $contentToDelete) {
                $contentToDelete->delete();
            }

            // Record moderation case
            $case = ModerationCase::create([
                'report_id' => $report?->id,
                'target_user_id' => $targetUser->id,
                'moderator_id' => $moderator->id,
                'action' => $action,
                'reason' => $reason,
                'expires_at' => $expiresAt,
            ]);

            // Update report if associated
            if ($report) {
                $report->update([
                    'status' => $action === 'dismiss' ? 'dismissed' : 'action_taken',
                    'reviewed_by_id' => $moderator->id,
                ]);
            }

            // Write audit log
            AuditLogger::log(
                action: "moderation.{$action}",
                user: $moderator,
                entityType: 'user',
                entityId: $targetUser->id,
                metadata: [
                    'case_id' => $case->id,
                    'reason' => $reason,
                    'report_id' => $report?->id,
                ]
            );

            return $case;
        });
    }
}
