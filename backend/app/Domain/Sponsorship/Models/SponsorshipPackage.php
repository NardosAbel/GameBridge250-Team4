<?php

namespace App\Domain\Sponsorship\Models;

use App\Domain\Sponsorship\Enums\SponsorshipTargetEnum;
use App\Domain\Sponsorship\Enums\SponsorshipTierEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SponsorshipPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'creator_id',
        'title',
        'slug',
        'target_type',
        'target_id',
        'tier',
        'price',
        'currency',
        'benefits',
        'deliverables',
        'duration_days',
        'audience_estimate',
        'is_published',
    ];

    protected static function booted(): void
    {
        static::creating(function (SponsorshipPackage $package): void {
            if (empty($package->uuid)) {
                $package->uuid = (string) Str::uuid();
            }
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->title).'-'.Str::random(4);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'target_type' => SponsorshipTargetEnum::class,
            'tier' => SponsorshipTierEnum::class,
            'price' => 'decimal:2',
            'benefits' => 'array',
            'deliverables' => 'array',
            'duration_days' => 'integer',
            'audience_estimate' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class, 'package_id');
    }
}
