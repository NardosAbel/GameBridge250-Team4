<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Models\LearningPath;
use App\Domain\Education\Models\LearningPathAssignment;
use App\Models\User;
use Carbon\Carbon;

class AssignLearningPathAction
{
    /**
     * @param  array<int, int>  $userIds
     * @return array<int, LearningPathAssignment>
     */
    public function execute(
        User $assigner,
        LearningPath $learningPath,
        ?string $universityName = null,
        array $userIds = [],
        ?Carbon $dueDate = null
    ): array {
        $assignments = [];

        if (! empty($userIds)) {
            foreach ($userIds as $userId) {
                $assignments[] = LearningPathAssignment::updateOrCreate(
                    [
                        'learning_path_id' => $learningPath->id,
                        'user_id' => $userId,
                    ],
                    [
                        'assigned_by_id' => $assigner->id,
                        'university_name' => $universityName ?? $learningPath->target_university,
                        'due_date' => $dueDate ?? $learningPath->deadline,
                        'status' => 'assigned',
                    ]
                );
            }
        } elseif (! empty($universityName)) {
            // Assign to all students belonging to the university
            $students = User::whereHas('profile', function ($q) use ($universityName) {
                $q->where('university', $universityName);
            })->get();

            foreach ($students as $student) {
                $assignments[] = LearningPathAssignment::updateOrCreate(
                    [
                        'learning_path_id' => $learningPath->id,
                        'user_id' => $student->id,
                    ],
                    [
                        'assigned_by_id' => $assigner->id,
                        'university_name' => $universityName,
                        'due_date' => $dueDate ?? $learningPath->deadline,
                        'status' => 'assigned',
                    ]
                );
            }
        }

        return $assignments;
    }
}
