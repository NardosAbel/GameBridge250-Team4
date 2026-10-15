<?php

namespace App\Domain\Sponsorship\Actions;

use App\Domain\Sponsorship\Enums\SponsorshipTargetEnum;
use App\Domain\Sponsorship\Enums\SponsorshipTierEnum;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Models\User;

class CreateSponsorshipPackageAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $creator, array $data): SponsorshipPackage
    {
        return SponsorshipPackage::create([
            'creator_id' => $creator->id,
            'title' => $data['title'],
            'target_type' => $data['target_type'] ?? SponsorshipTargetEnum::TOURNAMENT->value,
            'target_id' => $data['target_id'] ?? null,
            'tier' => $data['tier'] ?? SponsorshipTierEnum::GOLD->value,
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'ETB',
            'benefits' => $data['benefits'] ?? [],
            'deliverables' => $data['deliverables'] ?? [],
            'duration_days' => $data['duration_days'] ?? 30,
            'audience_estimate' => $data['audience_estimate'] ?? 5000,
            'is_published' => $data['is_published'] ?? true,
        ]);
    }
}
