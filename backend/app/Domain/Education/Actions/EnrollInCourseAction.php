<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\CourseEnrollment;
use App\Models\User;

class EnrollInCourseAction
{
    public function execute(User $user, Course $course): CourseEnrollment
    {
        return CourseEnrollment::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'status' => 'active',
                'progress_percentage' => 0,
                'started_at' => now(),
            ]
        );
    }
}
