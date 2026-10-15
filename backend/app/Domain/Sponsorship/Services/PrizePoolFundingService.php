<?php

namespace App\Domain\Sponsorship\Services;

use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Models\PrizePoolContribution;
use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PrizePoolFundingService
{
    /**
     * @return array<string, mixed>
     */
    public function contribute(
        Tournament $tournament,
        float $amount,
        ?User $user = null,
        ?BrandProfile $brandProfile = null,
        ?string $donorName = null,
        bool $isAnonymous = false,
        ?string $message = null
    ): PrizePoolContribution {
        return DB::transaction(function () use ($tournament, $amount, $user, $brandProfile, $donorName, $isAnonymous, $message) {
            $contribution = PrizePoolContribution::create([
                'tournament_id' => $tournament->id,
                'sponsor_id' => $user?->id,
                'brand_profile_id' => $brandProfile?->id,
                'amount' => $amount,
                'currency' => $tournament->currency ?? 'ETB',
                'donor_name' => $isAnonymous ? 'Anonymous Sponsor' : ($donorName ?? $brandProfile?->company_name ?? $user?->name ?? 'Community Supporter'),
                'is_anonymous' => $isAnonymous,
                'message' => $message,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Add amount to tournament prize pool
            $tournament->increment('prize_pool', $amount);

            return $contribution;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getFundingProgress(Tournament $tournament): array
    {
        $contributions = PrizePoolContribution::with(['brandProfile:id,company_name,logo_url', 'sponsor:id,name'])
            ->where('tournament_id', $tournament->id)
            ->where('status', 'paid')
            ->latest()
            ->get();

        $totalContributed = (float) $contributions->sum('amount');
        $basePrize = (float) $tournament->prize_pool;
        $targetGoal = max($basePrize, $totalContributed);

        return [
            'tournament_id' => $tournament->id,
            'tournament_title' => $tournament->title,
            'current_prize_pool' => $basePrize,
            'total_contributed' => $totalContributed,
            'contributions_count' => $contributions->count(),
            'currency' => $tournament->currency ?? 'ETB',
            'top_contributors' => $contributions->take(5)->map(function ($c) {
                return [
                    'id' => $c->id,
                    'donor_name' => $c->donor_name,
                    'amount' => $c->amount,
                    'is_anonymous' => $c->is_anonymous,
                    'message' => $c->message,
                    'brand_logo' => $c->brandProfile?->logo_url,
                    'date' => $c->paid_at?->toIso8601String(),
                ];
            }),
        ];
    }
}
