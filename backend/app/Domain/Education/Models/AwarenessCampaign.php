<?php

namespace App\Domain\Education\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AwarenessCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'title_am',
        'slug',
        'category',
        'description',
        'description_am',
        'banner_url',
        'content_blocks',
        'shareable_assets',
        'linked_quiz_id',
        'linked_course_id',
        'status',
        'start_date',
        'end_date',
        'target_institutions',
        'analytics',
        'created_by_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (AwarenessCampaign $campaign): void {
            if (empty($campaign->uuid)) {
                $campaign->uuid = (string) Str::uuid();
            }
            if (empty($campaign->slug)) {
                $campaign->slug = Str::slug($campaign->title).'-'.Str::random(4);
            }
            if (empty($campaign->analytics)) {
                $campaign->analytics = [
                    'views_count' => 0,
                    'shares_count' => 0,
                    'quiz_completions' => 0,
                ];
            }
        });
    }

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'shareable_assets' => 'array',
            'target_institutions' => 'array',
            'analytics' => 'array',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function linkedCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'linked_course_id');
    }

    public function linkedQuiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'linked_quiz_id');
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
