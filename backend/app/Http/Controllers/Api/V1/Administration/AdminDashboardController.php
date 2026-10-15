<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Domain\Administration\Models\PlatformFeatureFlag;
use App\Domain\Administration\Services\PlatformAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminDashboardController extends Controller
{
    public function overview(PlatformAnalyticsService $analyticsService): JsonResponse
    {
        Gate::authorize('view-platform-analytics');

        $metrics = $analyticsService->getExecutiveMetrics();
        $featureFlags = PlatformFeatureFlag::all();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'feature_flags' => $featureFlags,
            ],
        ]);
    }

    public function featureFlags(): JsonResponse
    {
        Gate::authorize('manage-feature-flags');

        $flags = PlatformFeatureFlag::with('updatedBy:id,name')->get();

        return response()->json([
            'success' => true,
            'data' => $flags,
        ]);
    }

    public function toggleFlag(Request $request, string $key): JsonResponse
    {
        Gate::authorize('manage-feature-flags');

        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
        ]);

        $flag = PlatformFeatureFlag::where('key', $key)->firstOrFail();
        $flag->update([
            'is_enabled' => $validated['is_enabled'],
            'updated_by_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Feature flag '{$flag->name}' updated successfully.",
            'data' => $flag->fresh(),
        ]);
    }

    public function systemHealth(): JsonResponse
    {
        Gate::authorize('view-system-health');

        $dbStatus = 'healthy';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'unhealthy';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $dbStatus === 'healthy' ? 'operational' : 'degraded',
                'database' => $dbStatus,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }
}
