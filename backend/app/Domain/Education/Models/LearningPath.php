<?php

namespace App\Domain\Education\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LearningPath extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'title_am',
        'slug',
        'description',
        'description_am',
        'banner_url',
        'institution_type',
        'target_university',
        'is_mandatory',
        'deadline',
        'created_by_id',
        'is_published',
    ];

    protected static function booted(): void
    {
        static::creating(function (LearningPath $path): void {
            if (empty($path->uuid)) {
                $path->uuid = (string) Str::uuid();
            }
            if (empty($path->slug)) {
                $path->slug = Str::slug($path->title).'-'.Str::random(4);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'is_published' => 'boolean',
            'deadline' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'learning_path_courses')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LearningPathAssignment::class);
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
