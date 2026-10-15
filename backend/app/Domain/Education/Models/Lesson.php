<?php

namespace App\Domain\Education\Models;

use App\Domain\Education\Enums\LessonTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'course_id',
        'module_id',
        'title',
        'title_am',
        'slug',
        'lesson_type',
        'content',
        'content_am',
        'media_url',
        'video_embed_url',
        'pdf_url',
        'interactive_payload',
        'order',
        'duration_minutes',
        'is_preview',
        'is_downloadable',
        'offline_package',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson): void {
            if (empty($lesson->uuid)) {
                $lesson->uuid = (string) Str::uuid();
            }
            if (empty($lesson->slug)) {
                $lesson->slug = Str::slug($lesson->title).'-'.Str::random(4);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'lesson_type' => LessonTypeEnum::class,
            'interactive_payload' => 'array',
            'offline_package' => 'array',
            'order' => 'integer',
            'duration_minutes' => 'integer',
            'is_preview' => 'boolean',
            'is_downloadable' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'am' && ! empty($this->title_am)) {
            return $this->title_am;
        }

        return $this->title;
    }

    public function localizedContent(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'am' && ! empty($this->content_am)) {
            return $this->content_am;
        }

        return $this->content ?? '';
    }
}
