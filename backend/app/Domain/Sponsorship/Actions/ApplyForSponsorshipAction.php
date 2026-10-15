<?php

namespace App\Domain\Sponsorship\Actions;

use App\Domain\Sponsorship\Enums\DeliverableStatusEnum;
use App\Domain\Sponsorship\Enums\SponsorshipStatusEnum;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplyForSponsorshipAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $applicant, BrandProfile $brandProfile, ?SponsorshipPackage $package = null, array $data = []): Sponsorship
    {
        return DB::transaction(function () use ($applicant, $brandProfile, $package, $data) {
            $amount = $package ? $package->price : ($data['amount'] ?? 10000.00);
            $currency = $package ? $package->currency : ($data['currency'] ?? 'ETB');
            $packageName = $package ? $package->title : ($data['package_name'] ?? 'Custom Sponsorship Proposal');
            $targetType = $package ? $package->target_type->value : ($data['target_type'] ?? 'tournament');
            $targetId = $package ? $package->target_id : ($data['target_id'] ?? null);

            $sponsorship = Sponsorship::create([
                'sponsor_id' => $brandProfile->user_id,
                'brand_profile_id' => $brandProfile->id,
                'package_id' => $package?->id,
                'applicant_user_id' => $applicant->id,
                'package_name' => $packageName,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'amount' => $amount,
                'currency' => $currency,
                'status' => SponsorshipStatusEnum::APPLIED,
                'contract_terms' => $data['contract_terms'] ?? 'Standard Sponsorship Terms & Deliverables Agreement',
                'start_date' => now(),
                'end_date' => now()->addDays($package?->duration_days ?? 30),
            ]);

            // Create deliverables from package if defined
            $deliverables = $package?->deliverables ?? ($data['deliverables'] ?? []);
            foreach ($deliverables as $deliv) {
                SponsorshipDeliverable::create([
                    'sponsorship_id' => $sponsorship->id,
                    'title' => is_array($deliv) ? ($deliv['title'] ?? 'Deliverable') : (string) $deliv,
                    'description' => is_array($deliv) ? ($deliv['description'] ?? null) : null,
                    'deliverable_type' => is_array($deliv) ? ($deliv['deliverable_type'] ?? 'stream_overlay') : 'stream_overlay',
                    'due_date' => now()->addDays(14),
                    'status' => DeliverableStatusEnum::PENDING,
                ]);
            }

            return $sponsorship->load('deliverables');
        });
    }
}
