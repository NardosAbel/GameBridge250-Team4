<?php

namespace App\Domain\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'course_id',
        'title',
        'title_am',
        'description',
        'order',
    ];

    protected static function booted(): void
    {
        static::creating(function (CourseModule $module): void {
            if (empty($module->uuid)) {
                $module->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('order');
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'am' && ! empty($this->title_am)) {
            return $this->title_am;
        }

        return $this->title;
    }
}
