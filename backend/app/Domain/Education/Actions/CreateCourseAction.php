<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Models\Course;
use App\Models\User;

class CreateCourseAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $instructor, array $data): Course
    {
        return Course::create([
            'instructor_id' => $instructor->id,
            'title' => $data['title'],
            'title_am' => $data['title_am'] ?? null,
            'description' => $data['description'] ?? null,
            'description_am' => $data['description_am'] ?? null,
            'cover_image' => $data['cover_image'] ?? null,
            'category' => $data['category'],
            'level' => $data['level'] ?? 'beginner',
            'status' => $data['status'] ?? CourseStatusEnum::PUBLISHED->value,
            'is_published' => ($data['status'] ?? 'published') === 'published',
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'language_support' => $data['language_support'] ?? ['en', 'am'],
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? 60,
            'total_points' => $data['total_points'] ?? 100,
            'is_offline_available' => $data['is_offline_available'] ?? true,
        ]);
    }
}
