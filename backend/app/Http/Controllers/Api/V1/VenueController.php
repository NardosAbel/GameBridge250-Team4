<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Venues\Actions\AssignVenueStaffAction;
use App\Domain\Venues\Actions\CreateVenueAction;
use App\Domain\Venues\Actions\SubmitVenueReviewAction;
use App\Domain\Venues\Actions\UpdateVenueAction;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Services\VenueAnalyticsService;
use App\Domain\Venues\Services\VenueDiscoveryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venues\AssignVenueStaffRequest;
use App\Http\Requests\Venues\StoreVenueRequest;
use App\Http\Requests\Venues\StoreVenueReviewRequest;
use App\Http\Requests\Venues\UpdateVenueRequest;
use App\Http\Resources\VenueResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(Request $request, VenueDiscoveryService $discoveryService): JsonResponse
    {
        $facilities = $request->input('facilities');
        if (is_string($facilities)) {
            $facilities = array_filter(array_map('trim', explode(',', $facilities)));
        }

        $venues = $discoveryService->discover(
            latitude: $request->filled('latitude') ? (float) $request->input('latitude') : null,
            longitude: $request->filled('longitude') ? (float) $request->input('longitude') : null,
            radiusKm: (float) $request->input('radius_km', 25.0),
            city: $request->input('city'),
            facilities: $facilities,
            search: $request->input('search')
        );

        return response()->json([
            'success' => true,
            'message' => 'Venues retrieved successfully.',
            'data' => VenueResource::collection($venues),
        ]);
    }

    public function show(Venue $venue): JsonResponse
    {
        $venue->loadMissing(['owner', 'events', 'reviews.user']);

        return response()->json([
            'success' => true,
            'data' => new VenueResource($venue),
        ]);
    }

    public function store(StoreVenueRequest $request, CreateVenueAction $action): JsonResponse
    {
        $venue = $action->execute($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Venue profile created successfully.',
            'data' => new VenueResource($venue),
        ], 201);
    }

    public function update(UpdateVenueRequest $request, Venue $venue, UpdateVenueAction $action): JsonResponse
    {
        $updated = $action->execute($request->user(), $venue, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Venue updated successfully.',
            'data' => new VenueResource($updated),
        ]);
    }

    public function assignStaff(AssignVenueStaffRequest $request, Venue $venue, AssignVenueStaffAction $action): JsonResponse
    {
        $staff = $action->execute(
            $request->user(),
            $venue,
            (int) $request->input('user_id'),
            $request->input('role', 'scanner')
        );

        return response()->json([
            'success' => true,
            'message' => "Venue staff role '{$staff->role}' assigned successfully.",
            'data' => $staff,
        ]);
    }

    public function storeReview(StoreVenueReviewRequest $request, Venue $venue, SubmitVenueReviewAction $action): JsonResponse
    {
        $review = $action->execute($request->user(), $venue, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ]);
    }

    public function analytics(Venue $venue, VenueAnalyticsService $analyticsService, Request $request): JsonResponse
    {
        if (! $venue->isStaff($request->user(), 'manager')) {
            abort(403, 'Unauthorized to view venue analytics.');
        }

        $analytics = $analyticsService->getAnalytics($venue);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }
}
