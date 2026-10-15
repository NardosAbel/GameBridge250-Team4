<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Domain\Administration\Services\PlatformAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PlatformAnalyticsController extends Controller
{
    public function metrics(PlatformAnalyticsService $service): JsonResponse
    {
        Gate::authorize('view-platform-analytics');

        $metrics = $service->getExecutiveMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    public function exportCsv(PlatformAnalyticsService $service): Response
    {
        Gate::authorize('view-platform-analytics');

        $csv = $service->generatePlatformMetricsCsv();
        $filename = 'platform_executive_metrics_'.now()->format('Ymd_His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
