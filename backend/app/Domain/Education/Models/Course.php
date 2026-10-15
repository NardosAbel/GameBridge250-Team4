<?php

namespace App\Domain\Education\Models;

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'title_am',
        'slug',
        'description',
        'description_am',
        'cover_image',
        'category',
        'level',
        'instructor_id',
        'is_published',
        'status',
        'scheduled_for',
        'language_support',
        'estimated_duration_minutes',
        'total_points',
        'is_offline_available',
    ];

    protected static function booted(): void
    {
        static::creating(function (Course $course): void {
            if (empty($course->uuid)) {
                $course->uuid = (string) Str::uuid();
            }
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title).'-'.Str::random(5);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'category' => CourseCategoryEnum::class,
            'level' => CourseLevelEnum::class,
            'status' => CourseStatusEnum::class,
            'is_published' => 'boolean',
            'is_offline_available' => 'boolean',
            'language_support' => 'array',
            'scheduled_for' => 'datetime',
            'estimated_duration_minutes' => 'integer',
            'total_points' => 'integer',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
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
