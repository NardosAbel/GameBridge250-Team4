<?php

namespace App\Http\Controllers\Api\V1\Sponsorship;

use App\Domain\Sponsorship\Actions\CreateSponsorshipPackageAction;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SponsorshipPackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SponsorshipPackage::query()
            ->with('creator')
            ->where('is_published', true);

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        if ($request->filled('target_id')) {
            $query->where('target_id', $request->input('target_id'));
        }

        if ($request->filled('tier')) {
            $query->where('tier', $request->input('tier'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        $packages = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    public function store(Request $request, CreateSponsorshipPackageAction $action): JsonResponse
    {
        Gate::authorize('create', SponsorshipPackage::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'target_type' => 'required|string',
            'target_id' => 'nullable|integer',
            'tier' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'benefits' => 'nullable|array',
            'deliverables' => 'nullable|array',
            'duration_days' => 'required|integer|min:1',
            'audience_estimate' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        $package = $action->execute($request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Sponsorship package created successfully.',
            'data' => $package,
        ], 201);
    }

    public function show(SponsorshipPackage $sponsorshipPackage): JsonResponse
    {
        $sponsorshipPackage->load(['creator', 'sponsorships']);

        return response()->json([
            'success' => true,
            'data' => $sponsorshipPackage,
        ]);
    }
}
