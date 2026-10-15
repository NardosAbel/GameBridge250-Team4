<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Tournaments\Models\Bracket;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Services\BracketGenerationService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class GenerateBracketAction
{
    public function __construct(
        protected BracketGenerationService $bracketService,
        protected NotificationService $notificationService
    ) {}

    public function execute(User $actor, Tournament $tournament): Bracket
    {
        $isStaff = $tournament->organizer_id === $actor->id
            || $tournament->staff()->where('user_id', $actor->id)->whereIn('role', ['admin', 'co_organizer'])->exists()
            || $actor->hasRole('super-admin');

        if (! $isStaff) {
            throw new AuthorizationException('Only tournament organizers can generate brackets.');
        }

        $bracket = $this->bracketService->generate($tournament);

        $tournament->update(['status' => 'live']);

        AuditLogger::log(
            action: 'tournament.bracket_generated',
            user: $actor,
            entityType: 'tournament',
            entityId: $tournament->id,
            metadata: [
                'bracket_id' => $bracket->id,
                'format' => $tournament->format,
            ]
        );

        // Notify participants that brackets are live
        $participants = $tournament->approvedRegistrations()->with('user')->get();
        foreach ($participants as $reg) {
            if ($reg->user) {
                $this->notificationService->send(
                    $reg->user,
                    new GenericNotification(
                        title: "Brackets Generated: {$tournament->title}",
                        message: 'The tournament bracket has been generated and the tournament is now LIVE! Check your match schedule.',
                        extraData: ['tournament_id' => $tournament->id, 'bracket_id' => $bracket->id]
                    )
                );
            }
        }

        return $bracket;
    }
}
