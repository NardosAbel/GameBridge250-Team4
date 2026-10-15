<?php

namespace App\Domain\Education\Services;

use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\CourseEnrollment;
use App\Domain\Education\Models\LearningBadge;
use App\Domain\Education\Models\LearningStreak;
use App\Domain\Education\Models\Lesson;
use App\Domain\Education\Models\LessonProgress;
use App\Domain\Education\Models\UserBadge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LearningProgressService
{
    public function recordLessonCompletion(User $user, Lesson $lesson, int $timeSpentSeconds = 0): LessonProgress
    {
        return DB::transaction(function () use ($user, $lesson, $timeSpentSeconds) {
            $progress = LessonProgress::firstOrNew([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ]);
            $progress->course_id = $lesson->course_id;
            $progress->status = 'completed';
            $progress->time_spent_seconds = ($progress->time_spent_seconds ?? 0) + $timeSpentSeconds;
            $progress->completed_at = now();
            $progress->save();

            // Update course enrollment progress
            $this->updateCourseProgress($user, $lesson->course);

            // Update user learning streak and stats
            $this->updateUserStreak($user, 10); // 10 points per lesson

            // Check and award badges
            $this->evaluateBadges($user);

            return $progress->fresh();
        });
    }

    public function updateCourseProgress(User $user, Course $course): CourseEnrollment
    {
        $enrollment = CourseEnrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['status' => 'active', 'progress_percentage' => 0]
        );

        $totalLessons = $course->lessons()->count();
        if ($totalLessons === 0) {
            $enrollment->update([
                'progress_percentage' => 100,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $enrollment;
        }

        $completedLessons = LessonProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'completed')
            ->count();

        $percentage = (int) round(($completedLessons / $totalLessons) * 100);
        $percentage = min(100, $percentage);

        $isCompleted = ($percentage >= 100);

        $enrollment->update([
            'progress_percentage' => $percentage,
            'status' => $isCompleted ? 'completed' : 'active',
            'completed_at' => $isCompleted ? ($enrollment->completed_at ?? now()) : null,
        ]);

        return $enrollment;
    }

    public function updateUserStreak(User $user, int $pointsToAdd = 0): LearningStreak
    {
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

        $today = Carbon::today();
        $lastActivity = $streak->last_activity_date ? Carbon::parse($streak->last_activity_date)->startOfDay() : null;

        $newStreak = $streak->current_streak;

        if (! $lastActivity) {
            $newStreak = 1;
        } elseif ($lastActivity->diffInDays($today) === 1) {
            // Consecutive day
            $newStreak++;
        } elseif ($lastActivity->diffInDays($today) > 1) {
            // Streak broken
            $newStreak = 1;
        }
        // If lastActivity was today, streak remains unchanged

        $longest = max($streak->longest_streak, $newStreak);

        $streak->update([
            'current_streak' => $newStreak,
            'longest_streak' => $longest,
            'last_activity_date' => $today->toDateString(),
            'total_lessons_completed' => LessonProgress::where('user_id', $user->id)->where('status', 'completed')->count(),
            'total_points' => $streak->total_points + $pointsToAdd,
        ]);

        return $streak->fresh();
    }

    public function evaluateBadges(User $user): void
    {
        $streak = LearningStreak::where('user_id', $user->id)->first();
        $totalCompleted = $streak?->total_lessons_completed ?? 0;
        $currentStreak = $streak?->current_streak ?? 0;

        // 1. First lesson badge
        if ($totalCompleted >= 1) {
            $this->awardBadgeIfEligible($user, 'first_lesson', 'First Step', 'የመጀመሪያ እርምጃ', 'Completed your first digital awareness lesson', 'የመጀመሪያውን የዲጂታል ግንዛቤ ትምህርት አጠናቀዋል', 'award');
        }

        // 2. Consistent scholar (3-day streak)
        if ($currentStreak >= 3) {
            $this->awardBadgeIfEligible($user, 'streak_3', 'Consistent Scholar', 'ቀጣይነት ያለው ተማሪ', 'Maintained a 3-day continuous learning streak', 'የ3 ቀን ተከታታይ የትምህርት ጉዞ አጠናቀዋል', 'zap');
        }

        // 3. Cyber Shield badge (completed online safety or scam awareness lesson)
        $hasSafety = LessonProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('course', function ($q) {
                $q->whereIn('category', ['online_safety', 'scam_awareness', 'cyberbullying_prevention']);
            })
            ->exists();

        if ($hasSafety) {
            $this->awardBadgeIfEligible($user, 'cyber_shield', 'Cyber Shield', 'የሳይበር ጋሻ', 'Completed an online safety & cyber protection lesson', 'የመስመር ላይ ደህንነት እና የሳይበር ጥበቃ ትምህርት አጠናቀዋል', 'shield-check');
        }
    }

    public function awardBadgeIfEligible(User $user, string $slug, string $name, string $nameAm, string $desc, string $descAm, string $icon): void
    {
        $badge = LearningBadge::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'name_am' => $nameAm,
                'description' => $desc,
                'description_am' => $descAm,
                'icon' => $icon,
                'criteria_type' => $slug,
                'criteria_threshold' => 1,
            ]
        );

        UserBadge::firstOrCreate([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
    }
}
