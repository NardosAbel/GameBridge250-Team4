<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Domain\Administration\Services\CreatorAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CreatorAnalyticsController extends Controller
{
    public function insights(Request $request, CreatorAnalyticsService $service): JsonResponse
    {
        Gate::authorize('view-creator-analytics');

        $insights = $service->getCreatorInsights($request->user());

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }
}
