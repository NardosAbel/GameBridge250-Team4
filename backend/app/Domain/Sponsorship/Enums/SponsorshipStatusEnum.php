<?php

namespace App\Domain\Sponsorship\Enums;

enum SponsorshipStatusEnum: string
{
    case APPLIED = 'applied';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case CONTRACT_SIGNED = 'contract_signed';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
