<?php

namespace App\Domain\Education\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'course_id',
        'status',
        'progress_percentage',
        'last_accessed_lesson_id',
        'started_at',
        'completed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (CourseEnrollment $enrollment): void {
            if (empty($enrollment->uuid)) {
                $enrollment->uuid = (string) Str::uuid();
            }
            if (empty($enrollment->started_at)) {
                $enrollment->started_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lastAccessedLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'last_accessed_lesson_id');
    }
}
