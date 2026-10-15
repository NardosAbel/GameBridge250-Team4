<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Tournaments\Models\Dispute;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ResolveDisputeAction
{
    public function __construct(
        protected ConfirmMatchResultAction $confirmAction,
        protected OverrideMatchResultAction $overrideAction,
        protected NotificationService $notificationService
    ) {}

    public function execute(User $actor, Dispute $dispute, array $data): Dispute
    {
        $match = $dispute->match;
        $tournament = $match->tournament;

        $isStaff = $tournament->organizer_id === $actor->id
            || $tournament->staff()->where('user_id', $actor->id)->whereIn('role', ['admin', 'co_organizer', 'referee'])->exists()
            || $match->referee_id === $actor->id
            || $actor->hasRole('super-admin');

        if (! $isStaff) {
            throw new AuthorizationException('Only authorized tournament referees or organizers can resolve disputes.');
        }

        return DB::transaction(function () use ($actor, $dispute, $match, $data) {
            $actionTaken = $data['action_taken']; // confirm_result, overturn_result, rematch, disqualify_participant
            $resolutionNotes = $data['resolution_notes'] ?? 'Dispute resolved by official.';

            $dispute->update([
                'assigned_to_id' => $actor->id,
                'status' => 'resolved',
                'resolution_notes' => $resolutionNotes,
                'action_taken' => $actionTaken,
                'resolved_at' => now(),
            ]);

            switch ($actionTaken) {
                case 'confirm_result':
                    if ($match->latestResult) {
                        $this->confirmAction->execute($actor, $match, $match->latestResult);
                    }
                    break;

                case 'overturn_result':
                    $this->overrideAction->execute($actor, $match, [
                        'participant1_score' => $data['participant1_score'] ?? $match->participant1_score,
                        'participant2_score' => $data['participant2_score'] ?? $match->participant2_score,
                        'winner_id' => $data['winner_id'] ?? $match->winner_id,
                        'reason' => $resolutionNotes,
                    ]);
                    break;

                case 'rematch':
                    $match->update([
                        'participant1_score' => 0,
                        'participant2_score' => 0,
                        'participant1_ready' => false,
                        'participant2_ready' => false,
                        'winner_id' => null,
                        'status' => 'ready',
                        'completed_at' => null,
                    ]);
                    break;

                case 'disqualify_participant':
                    $disqualifiedId = $data['disqualified_id'] ?? $match->participant1_id;
                    $winnerId = ($disqualifiedId === $match->participant1_id)
                        ? $match->participant2_id
                        : $match->participant1_id;

                    $this->overrideAction->execute($actor, $match, [
                        'participant1_score' => ($winnerId === $match->participant1_id) ? 1 : 0,
                        'participant2_score' => ($winnerId === $match->participant2_id) ? 1 : 0,
                        'winner_id' => $winnerId,
                        'reason' => "Disqualification of participant #{$disqualifiedId}: ".$resolutionNotes,
                    ]);
                    break;
            }

            AuditLogger::log(
                action: 'match.dispute_resolved',
                user: $actor,
                entityType: 'dispute',
                entityId: $dispute->id,
                metadata: [
                    'action_taken' => $actionTaken,
                    'match_id' => $match->id,
                ]
            );

            // Notify dispute creator
            if ($dispute->raisedBy) {
                $this->notificationService->send(
                    $dispute->raisedBy,
                    new GenericNotification(
                        title: "Dispute Resolved: Match #{$match->id}",
                        message: "Your dispute for Match #{$match->id} has been resolved: {$actionTaken}. Notes: {$resolutionNotes}",
                        extraData: ['match_id' => $match->id, 'dispute_id' => $dispute->id]
                    )
                );
            }

            return $dispute;
        });
    }
}
