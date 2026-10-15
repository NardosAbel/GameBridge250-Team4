<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Tournaments\Models\Dispute;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OpenDisputeAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function execute(User $actor, TournamentMatch $match, array $data): Dispute
    {
        if (! $this->isMatchParticipant($actor, $match)) {
            throw new AuthorizationException('Only match participants can raise a dispute.');
        }

        if ($match->status === 'disputed') {
            throw new InvalidArgumentException('There is already an active dispute for this match.');
        }

        return DB::transaction(function () use ($actor, $match, $data) {
            $dispute = Dispute::create([
                'match_id' => $match->id,
                'raised_by_id' => $actor->id,
                'reason' => $data['reason'],
                'evidence_urls' => $data['evidence_urls'] ?? [],
                'status' => 'open',
            ]);

            $match->update(['status' => 'disputed']);

            // Update latest result status if submitted
            if ($match->latestResult && $match->latestResult->status === 'submitted') {
                $match->latestResult->update(['status' => 'disputed']);
            }

            AuditLogger::log(
                action: 'match.dispute_opened',
                user: $actor,
                entityType: 'match',
                entityId: $match->id,
                metadata: [
                    'dispute_id' => $dispute->id,
                    'reason' => $data['reason'],
                ]
            );

            // Notify tournament organizer and staff
            $tournament = $match->tournament;
            $staff = $tournament->staff()->with('user')->get();
            foreach ($staff as $member) {
                if ($member->user) {
                    $this->notificationService->send(
                        $member->user,
                        new GenericNotification(
                            title: "Dispute Raised: Match #{$match->id}",
                            message: "A dispute has been submitted for match #{$match->id} in tournament {$tournament->title}.",
                            extraData: ['match_id' => $match->id, 'dispute_id' => $dispute->id]
                        )
                    );
                }
            }

            return $dispute;
        });
    }

    protected function isMatchParticipant(User $actor, TournamentMatch $match): bool
    {
        if ($match->participant1_type === 'team') {
            $p1Team = $match->participant1_entity;
            if ($p1Team && $p1Team->members()->where('user_id', $actor->id)->exists()) {
                return true;
            }
        } elseif ($match->participant1_id === $actor->id) {
            return true;
        }

        if ($match->participant2_type === 'team') {
            $p2Team = $match->participant2_entity;
            if ($p2Team && $p2Team->members()->where('user_id', $actor->id)->exists()) {
                return true;
            }
        } elseif ($match->participant2_id === $actor->id) {
            return true;
        }

        return false;
    }
}
