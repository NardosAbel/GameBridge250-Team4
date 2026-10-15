<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Models\AwarenessCampaign;
use App\Models\User;

class CreateAwarenessCampaignAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $creator, array $data): AwarenessCampaign
    {
        return AwarenessCampaign::create([
            'created_by_id' => $creator->id,
            'title' => $data['title'],
            'title_am' => $data['title_am'] ?? null,
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'description_am' => $data['description_am'] ?? null,
            'banner_url' => $data['banner_url'] ?? null,
            'content_blocks' => $data['content_blocks'] ?? [],
            'shareable_assets' => $data['shareable_assets'] ?? [],
            'linked_quiz_id' => $data['linked_quiz_id'] ?? null,
            'linked_course_id' => $data['linked_course_id'] ?? null,
            'status' => $data['status'] ?? 'published',
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'target_institutions' => $data['target_institutions'] ?? [],
            'analytics' => [
                'views_count' => 0,
                'shares_count' => 0,
                'quiz_completions' => 0,
            ],
        ]);
    }
}
