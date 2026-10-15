<?php

namespace App\Http\Controllers\Api\V1\Sponsorship;

use App\Domain\Sponsorship\Actions\SubmitDeliverableEvidenceAction;
use App\Domain\Sponsorship\Actions\VerifyDeliverableAction;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeliverableController extends Controller
{
    public function submitEvidence(
        Request $request,
        SponsorshipDeliverable $deliverable,
        SubmitDeliverableEvidenceAction $action
    ): JsonResponse {
        Gate::authorize('submitEvidence', $deliverable);

        $validated = $request->validate([
            'evidence_url' => 'required|url|max:500',
            'evidence_notes' => 'nullable|string|max:1000',
        ]);

        $updated = $action->execute(
            $deliverable,
            $validated['evidence_url'],
            $validated['evidence_notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Deliverable evidence submitted successfully.',
            'data' => $updated,
        ]);
    }

    public function verify(
        Request $request,
        SponsorshipDeliverable $deliverable,
        VerifyDeliverableAction $action
    ): JsonResponse {
        Gate::authorize('verify', $deliverable);

        $validated = $request->validate([
            'verified' => 'required|boolean',
            'rejection_notes' => 'nullable|string|max:1000',
        ]);

        $updated = $action->execute(
            $request->user(),
            $deliverable,
            $validated['verified'],
            $validated['rejection_notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => $validated['verified'] ? 'Deliverable marked as verified.' : 'Deliverable rejected.',
            'data' => $updated,
        ]);
    }
}
