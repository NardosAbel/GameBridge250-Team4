<?php

namespace App\Domain\Sponsorship\Actions;

use App\Domain\Sponsorship\Enums\DeliverableStatusEnum;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;
use App\Models\User;

class VerifyDeliverableAction
{
    public function execute(
        User $verifier,
        SponsorshipDeliverable $deliverable,
        bool $verified,
        ?string $rejectionNotes = null
    ): SponsorshipDeliverable {
        if ($verified) {
            $deliverable->update([
                'status' => DeliverableStatusEnum::VERIFIED,
                'verified_by_id' => $verifier->id,
                'verified_at' => now(),
            ]);
        } else {
            $deliverable->update([
                'status' => DeliverableStatusEnum::REJECTED,
                'rejection_notes' => $rejectionNotes,
            ]);
        }

        return $deliverable->fresh();
    }
}
