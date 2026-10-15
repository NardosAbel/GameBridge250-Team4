<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Models\LearningPath;
use App\Models\User;

class CreateLearningPathAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $courseIds
     */
    public function execute(User $creator, array $data, array $courseIds = []): LearningPath
    {
        $path = LearningPath::create([
            'created_by_id' => $creator->id,
            'title' => $data['title'],
            'title_am' => $data['title_am'] ?? null,
            'description' => $data['description'] ?? null,
            'description_am' => $data['description_am'] ?? null,
            'banner_url' => $data['banner_url'] ?? null,
            'institution_type' => $data['institution_type'] ?? 'all',
            'target_university' => $data['target_university'] ?? null,
            'is_mandatory' => $data['is_mandatory'] ?? false,
            'deadline' => $data['deadline'] ?? null,
            'is_published' => $data['is_published'] ?? true,
        ]);

        if (! empty($courseIds)) {
            $syncData = [];
            foreach ($courseIds as $order => $courseId) {
                $syncData[$courseId] = ['order' => $order + 1];
            }
            $path->courses()->sync($syncData);
        }

        return $path->load('courses');
    }
}
