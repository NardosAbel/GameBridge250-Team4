<?php

namespace App\Domain\Sponsorship\Enums;

enum SponsorshipTargetEnum: string
{
    case TOURNAMENT = 'tournament';
    case TEAM = 'team';
    case PLAYER = 'player';
    case VENUE = 'venue';
    case CREATOR = 'creator';
    case LEAGUE = 'league';
    case AWARENESS_CAMPAIGN = 'awareness_campaign';
    case PLATFORM = 'platform';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
