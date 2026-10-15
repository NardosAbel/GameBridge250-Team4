<?php

namespace App\Domain\Education\Services;

use App\Domain\Education\Models\AwarenessCampaign;

class CampaignAnalyticsService
{
    public function recordView(AwarenessCampaign $campaign): void
    {
        $analytics = $campaign->analytics ?? ['views_count' => 0, 'shares_count' => 0, 'quiz_completions' => 0];
        $analytics['views_count'] = ($analytics['views_count'] ?? 0) + 1;
        $campaign->update(['analytics' => $analytics]);
    }

    public function recordShare(AwarenessCampaign $campaign, string $platform = 'general'): void
    {
        $analytics = $campaign->analytics ?? ['views_count' => 0, 'shares_count' => 0, 'quiz_completions' => 0];
        $analytics['shares_count'] = ($analytics['shares_count'] ?? 0) + 1;
        $analytics['platforms'][$platform] = ($analytics['platforms'][$platform] ?? 0) + 1;
        $campaign->update(['analytics' => $analytics]);
    }

    public function recordQuizCompletion(AwarenessCampaign $campaign): void
    {
        $analytics = $campaign->analytics ?? ['views_count' => 0, 'shares_count' => 0, 'quiz_completions' => 0];
        $analytics['quiz_completions'] = ($analytics['quiz_completions'] ?? 0) + 1;
        $campaign->update(['analytics' => $analytics]);
    }
}
