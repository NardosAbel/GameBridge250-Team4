<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Models\Lesson;
use App\Domain\Education\Models\LessonProgress;
use App\Domain\Education\Services\LearningProgressService;
use App\Models\User;

class CompleteLessonAction
{
    public function __construct(
        protected LearningProgressService $progressService
    ) {}

    public function execute(User $user, Lesson $lesson, int $timeSpentSeconds = 0): LessonProgress
    {
        return $this->progressService->recordLessonCompletion($user, $lesson, $timeSpentSeconds);
    }
}
