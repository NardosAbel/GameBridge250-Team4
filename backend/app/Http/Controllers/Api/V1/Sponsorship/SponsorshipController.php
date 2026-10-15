<?php

namespace App\Http\Controllers\Api\V1\Sponsorship;

use App\Domain\Sponsorship\Actions\ApplyForSponsorshipAction;
use App\Domain\Sponsorship\Actions\ReviewSponsorshipApplicationAction;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Domain\Sponsorship\Services\SponsorshipReportingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SponsorshipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Sponsorship::query()
            ->with(['brandProfile', 'package', 'deliverables']);

        if (! $user->hasRole('super_admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('applicant_user_id', $user->id)
                    ->orWhere('sponsor_id', $user->id)
                    ->orWhereHas('package', function ($pq) use ($user) {
                        $pq->where('creator_id', $user->id);
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        $sponsorships = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $sponsorships,
        ]);
    }

    public function apply(Request $request, ApplyForSponsorshipAction $action): JsonResponse
    {
        Gate::authorize('create', Sponsorship::class);

        $validated = $request->validate([
            'brand_profile_id' => 'required|exists:brand_profiles,id',
            'package_id' => 'nullable|exists:sponsorship_packages,id',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'package_name' => 'nullable|string|max:255',
            'target_type' => 'nullable|string',
            'target_id' => 'nullable|integer',
            'contract_terms' => 'nullable|string',
            'deliverables' => 'nullable|array',
        ]);

        $brandProfile = BrandProfile::findOrFail($validated['brand_profile_id']);
        if ($brandProfile->user_id !== $request->user()->id && ! $request->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to apply on behalf of this brand.',
            ], 403);
        }

        $package = isset($validated['package_id']) ? SponsorshipPackage::find($validated['package_id']) : null;

        $sponsorship = $action->execute(
            $request->user(),
            $brandProfile,
            $package,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Sponsorship application submitted successfully.',
            'data' => $sponsorship,
        ], 201);
    }

    public function show(Sponsorship $sponsorship): JsonResponse
    {
        Gate::authorize('view', $sponsorship);

        $sponsorship->load(['brandProfile', 'package', 'deliverables', 'placements', 'applicant', 'reviewer']);

        return response()->json([
            'success' => true,
            'data' => $sponsorship,
        ]);
    }

    public function review(
        Request $request,
        Sponsorship $sponsorship,
        ReviewSponsorshipApplicationAction $action
    ): JsonResponse {
        Gate::authorize('review', $sponsorship);

        $validated = $request->validate([
            'approve' => 'required|boolean',
            'rejection_reason' => 'nullable|string|max:1000',
            'contract_terms' => 'nullable|string',
        ]);

        $sponsorship = $action->execute(
            $request->user(),
            $sponsorship,
            $validated['approve'],
            $validated['rejection_reason'] ?? null,
            $validated['contract_terms'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => $validated['approve'] ? 'Sponsorship application approved and activated.' : 'Sponsorship application rejected.',
            'data' => $sponsorship,
        ]);
    }

    public function report(Sponsorship $sponsorship, SponsorshipReportingService $reportingService): JsonResponse
    {
        Gate::authorize('view', $sponsorship);

        $report = $reportingService->generateReport($sponsorship);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function exportReport(Sponsorship $sponsorship, SponsorshipReportingService $reportingService): Response
    {
        Gate::authorize('exportReport', $sponsorship);

        $csv = $reportingService->generateCsvReport($sponsorship);
        $filename = "sponsorship_report_{$sponsorship->contract_number}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
