<?php

namespace App\Domain\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'course_id',
        'lesson_id',
        'title',
        'title_am',
        'description',
        'description_am',
        'passing_score',
        'max_retries',
        'time_limit_minutes',
        'questions',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quiz $quiz): void {
            if (empty($quiz->uuid)) {
                $quiz->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'max_retries' => 'integer',
            'time_limit_minutes' => 'integer',
            'questions' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'am' && ! empty($this->title_am)) {
            return $this->title_am;
        }

        return $this->title;
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'am' && ! empty($this->description_am)) {
            return $this->description_am;
        }

        return $this->description;
    }
}
