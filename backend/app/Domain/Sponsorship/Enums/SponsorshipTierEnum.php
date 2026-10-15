<?php

namespace App\Domain\Sponsorship\Enums;

enum SponsorshipTierEnum: string
{
    case TITLE = 'title';
    case PRESENTING = 'presenting';
    case GOLD = 'gold';
    case SILVER = 'silver';
    case BRONZE = 'bronze';
    case CUSTOM = 'custom';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
