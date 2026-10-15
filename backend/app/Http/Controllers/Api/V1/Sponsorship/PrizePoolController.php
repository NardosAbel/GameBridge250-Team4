<?php

namespace App\Http\Controllers\Api\V1\Sponsorship;

use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Services\PrizePoolFundingService;
use App\Domain\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrizePoolController extends Controller
{
    public function progress(Tournament $tournament, PrizePoolFundingService $service): JsonResponse
    {
        $progress = $service->getFundingProgress($tournament);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    public function contribute(
        Request $request,
        Tournament $tournament,
        PrizePoolFundingService $service
    ): JsonResponse {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:50',
            'brand_profile_id' => 'nullable|exists:brand_profiles,id',
            'donor_name' => 'nullable|string|max:255',
            'is_anonymous' => 'nullable|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $brandProfile = null;
        if (! empty($validated['brand_profile_id'])) {
            $brandProfile = BrandProfile::findOrFail($validated['brand_profile_id']);
        }

        $contribution = $service->contribute(
            $tournament,
            (float) $validated['amount'],
            $user,
            $brandProfile,
            $validated['donor_name'] ?? null,
            (bool) ($validated['is_anonymous'] ?? false),
            $validated['message'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Prize pool contribution successfully recorded.',
            'data' => $contribution,
        ], 201);
    }
}
