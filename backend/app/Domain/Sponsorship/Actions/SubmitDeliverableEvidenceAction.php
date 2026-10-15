<?php

namespace App\Domain\Sponsorship\Actions;

use App\Domain\Sponsorship\Enums\DeliverableStatusEnum;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;

class SubmitDeliverableEvidenceAction
{
    public function execute(
        SponsorshipDeliverable $deliverable,
        string $evidenceUrl,
        ?string $evidenceNotes = null
    ): SponsorshipDeliverable {
        $deliverable->update([
            'evidence_url' => $evidenceUrl,
            'evidence_notes' => $evidenceNotes,
            'status' => DeliverableStatusEnum::SUBMITTED,
        ]);

        return $deliverable->fresh();
    }
}
