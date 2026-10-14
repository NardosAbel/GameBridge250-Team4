<?php

namespace App\Domain\Identity\Enums;

enum RoleEnum: string
{
    case PLAYER = 'player';
    case TEAM_CAPTAIN = 'team_captain';
    case ORGANIZER = 'organizer';
    case VENUE_ADMIN = 'venue_admin';
    case SCHOOL_ADMIN = 'school_admin';
    case SPONSOR = 'sponsor';
    case CREATOR = 'creator';
    case MODERATOR = 'moderator';
    case SUPER_ADMIN = 'super_admin';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
