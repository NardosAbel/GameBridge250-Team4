<?php

namespace App\Domain\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_am',
        'description',
        'description_am',
        'icon',
        'criteria_type',
        'criteria_threshold',
    ];

    protected function casts(): array
    {
        return [
            'criteria_threshold' => 'integer',
        ];
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'badge_id');
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'am' && ! empty($this->name_am)) {
            return $this->name_am;
        }

        return $this->name;
    }
}
