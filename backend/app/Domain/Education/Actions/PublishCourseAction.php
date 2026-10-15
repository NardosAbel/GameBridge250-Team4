<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Models\Course;

class PublishCourseAction
{
    public function execute(Course $course): Course
    {
        $course->update([
            'status' => CourseStatusEnum::PUBLISHED,
            'is_published' => true,
        ]);

        return $course->fresh();
    }
}
