<?php

namespace App\Http\Controllers\Api\V1\Sponsorship;

use App\Domain\Sponsorship\Actions\CreateBrandProfileAction;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BrandProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BrandProfile::query()->with('user');

        if ($request->boolean('verified_only')) {
            $query->where('verified', true);
        }

        if ($request->filled('industry')) {
            $query->where('industry', $request->input('industry'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $profiles = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    public function store(Request $request, CreateBrandProfileAction $action): JsonResponse
    {
        Gate::authorize('create', BrandProfile::class);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'industry' => 'required|string|max:100',
            'logo_url' => 'nullable|url|max:500',
            'banner_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'social_links' => 'nullable|array',
        ]);

        $profile = $action->execute($request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Brand profile created successfully.',
            'data' => $profile,
        ], 201);
    }

    public function show(BrandProfile $brandProfile): JsonResponse
    {
        $brandProfile->load(['user', 'sponsorships', 'placements', 'prizePoolContributions']);

        return response()->json([
            'success' => true,
            'data' => $brandProfile,
        ]);
    }

    public function update(Request $request, BrandProfile $brandProfile): JsonResponse
    {
        Gate::authorize('update', $brandProfile);

        $validated = $request->validate([
            'company_name' => 'sometimes|required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'industry' => 'sometimes|required|string|max:100',
            'logo_url' => 'nullable|url|max:500',
            'banner_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'contact_email' => 'sometimes|required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'social_links' => 'nullable|array',
            'verified' => 'sometimes|boolean',
        ]);

        if (isset($validated['verified']) && ! $request->user()->hasRole('super_admin')) {
            unset($validated['verified']);
        }

        $brandProfile->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Brand profile updated successfully.',
            'data' => $brandProfile->fresh(),
        ]);
    }
}
