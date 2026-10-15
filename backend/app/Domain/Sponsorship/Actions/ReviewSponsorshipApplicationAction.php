<?php

namespace App\Domain\Sponsorship\Actions;

use App\Domain\Sponsorship\Enums\SponsorshipStatusEnum;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Models\User;

class ReviewSponsorshipApplicationAction
{
    public function execute(
        User $reviewer,
        Sponsorship $sponsorship,
        bool $approve,
        ?string $rejectionReason = null,
        ?string $contractTerms = null
    ): Sponsorship {
        if ($approve) {
            $sponsorship->update([
                'reviewed_by_id' => $reviewer->id,
                'status' => SponsorshipStatusEnum::ACTIVE,
                'contract_terms' => $contractTerms ?? $sponsorship->contract_terms,
                'start_date' => now(),
            ]);
        } else {
            $sponsorship->update([
                'reviewed_by_id' => $reviewer->id,
                'status' => SponsorshipStatusEnum::REJECTED,
                'rejection_reason' => $rejectionReason,
            ]);
        }

        return $sponsorship->fresh(['deliverables', 'brandProfile']);
    }
}
