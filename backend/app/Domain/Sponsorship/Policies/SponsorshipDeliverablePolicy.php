<?php

namespace App\Domain\Sponsorship\Policies;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;
use App\Models\User;

class SponsorshipDeliverablePolicy
{
    public function view(User $user, SponsorshipDeliverable $deliverable): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $sponsorship = $deliverable->sponsorship;
        if (! $sponsorship) {
            return false;
        }

        return $sponsorship->applicant_user_id === $user->id
            || $sponsorship->sponsor_id === $user->id
            || ($sponsorship->brandProfile && $sponsorship->brandProfile->user_id === $user->id)
            || ($sponsorship->package && $sponsorship->package->creator_id === $user->id);
    }

    public function submitEvidence(User $user, SponsorshipDeliverable $deliverable): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $sponsorship = $deliverable->sponsorship;
        if (! $sponsorship) {
            return false;
        }

        // Deliverables are usually submitted by the package creator/target organizer fulfilling the deliverable, or applicant
        return $sponsorship->applicant_user_id === $user->id
            || ($sponsorship->package && $sponsorship->package->creator_id === $user->id);
    }

    public function verify(User $user, SponsorshipDeliverable $deliverable): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $sponsorship = $deliverable->sponsorship;
        if (! $sponsorship) {
            return false;
        }

        // Verified by the sponsor / brand owner or package reviewer
        return $sponsorship->sponsor_id === $user->id
            || ($sponsorship->brandProfile && $sponsorship->brandProfile->user_id === $user->id)
            || ($sponsorship->package && $sponsorship->package->creator_id === $user->id);
    }
}
