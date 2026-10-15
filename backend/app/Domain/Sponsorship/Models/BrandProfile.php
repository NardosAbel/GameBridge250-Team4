<?php

namespace App\Domain\Sponsorship\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BrandProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'company_name',
        'slug',
        'tagline',
        'description',
        'industry',
        'logo_url',
        'banner_url',
        'website_url',
        'contact_email',
        'contact_phone',
        'social_links',
        'verified',
    ];

    protected static function booted(): void
    {
        static::creating(function (BrandProfile $profile): void {
            if (empty($profile->uuid)) {
                $profile->uuid = (string) Str::uuid();
            }
            if (empty($profile->slug)) {
                $profile->slug = Str::slug($profile->company_name).'-'.Str::random(4);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class, 'brand_profile_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(BrandPlacement::class, 'brand_profile_id');
    }

    public function prizePoolContributions(): HasMany
    {
        return $this->hasMany(PrizePoolContribution::class, 'brand_profile_id');
    }
}
