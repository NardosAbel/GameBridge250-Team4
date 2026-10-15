<?php

namespace App\Http\Controllers\Api\V1\Education;

use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Models\CourseEnrollment;
use App\Domain\Education\Models\LearningPathAssignment;
use App\Domain\Education\Models\LearningStreak;
use App\Domain\Education\Models\UserBadge;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Learning Streak & Stats
        $streak = LearningStreak::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity_date' => null,
                'total_lessons_completed' => 0,
                'total_quizzes_passed' => 0,
                'total_points' => 0,
            ]
        );

        // 2. Active Enrollments
        $enrollments = CourseEnrollment::with(['course:id,uuid,title,title_am,category,cover_image,level', 'lastAccessedLesson:id,uuid,title,title_am,slug'])
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->get();

        // 3. Badges
        $badges = UserBadge::with('badge')
            ->where('user_id', $user->id)
            ->latest('awarded_at')
            ->get()
            ->map(function ($ub) {
                return [
                    'id' => $ub->badge->id,
                    'slug' => $ub->badge->slug,
                    'name' => $ub->badge->name,
                    'name_am' => $ub->badge->name_am,
                    'description' => $ub->badge->description,
                    'description_am' => $ub->badge->description_am,
                    'icon' => $ub->badge->icon,
                    'awarded_at' => $ub->awarded_at->toIso8601String(),
                ];
            });

        // 4. Certificates
        $certificates = Certificate::with('course:id,uuid,title,title_am')
            ->where('user_id', $user->id)
            ->latest('issued_at')
            ->get();

        // 5. Assigned Learning Paths
        $assignedPaths = LearningPathAssignment::with(['learningPath.courses'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'streak' => $streak,
                'enrollments' => $enrollments,
                'badges' => $badges,
                'certificates' => $certificates,
                'assigned_paths' => $assignedPaths,
            ],
        ]);
    }
}
