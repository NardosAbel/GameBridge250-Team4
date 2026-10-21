<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\MatchEvidence;
use App\Domain\Tournaments\Models\MatchResult;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitMatchResultAction
{
    public function __construct(
        protected ConfirmMatchResultAction $confirmAction
    ) {}

    public function execute(User $actor, TournamentMatch $match, array $data): MatchResult
    {
        $tournament = $match->tournament;
        $isReferee = $tournament->staff()->where('user_id', $actor->id)->whereIn('role', ['admin', 'referee', 'scorekeeper'])->exists()
            || $match->referee_id === $actor->id
            || $actor->hasRole('super_admin');

        $isParticipant = $this->isMatchParticipant($actor, $match);

        if (! $isReferee && ! $isParticipant) {
            throw new AuthorizationException('You are not authorized to submit scores for this match.');
        }

        $score1 = (int) $data['participant1_score'];
        $score2 = (int) $data['participant2_score'];

        $scoreToWin = $match->getScoreToWin();
        if ($score1 < $scoreToWin && $score2 < $scoreToWin && $tournament->format !== 'round_robin') {
            throw new InvalidArgumentException("In {$match->match_format}, the winning score must be at least {$scoreToWin}.");
        }

        return DB::transaction(function () use ($actor, $match, $score1, $score2, $data, $isReferee) {
            $result = MatchResult::create([
                'match_id' => $match->id,
                'submitted_by_id' => $actor->id,
                'participant1_score' => $score1,
                'participant2_score' => $score2,
                'status' => 'submitted',
                'notes' => $data['notes'] ?? null,
                'game_metadata' => $data['game_metadata'] ?? null,
            ]);

            // Save any evidence attachments
            if (! empty($data['evidence']) && is_array($data['evidence'])) {
                foreach ($data['evidence'] as $item) {
                    MatchEvidence::create([
                        'match_result_id' => $result->id,
                        'user_id' => $actor->id,
                        'type' => $item['type'] ?? 'screenshot',
                        'url' => $item['url'],
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }

            AuditLogger::log(
                action: 'match.result_submitted',
                user: $actor,
                entityType: 'match',
                entityId: $match->id,
                metadata: [
                    'result_id' => $result->id,
                    'participant1_score' => $score1,
                    'participant2_score' => $score2,
                ]
            );

            // If submitted directly by an authorized referee or organizer, auto-confirm!
            if ($isReferee) {
                $this->confirmAction->execute($actor, $match, $result);
            }

            return $result;
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
