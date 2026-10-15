<?php

namespace App\Domain\Sponsorship\Models;

use App\Domain\Sponsorship\Enums\SponsorshipStatusEnum;
use App\Domain\Sponsorship\Enums\SponsorshipTargetEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sponsorship extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'sponsor_id',
        'brand_profile_id',
        'package_id',
        'applicant_user_id',
        'reviewed_by_id',
        'package_name',
        'target_type',
        'target_id',
        'amount',
        'currency',
        'contract_number',
        'contract_terms',
        'start_date',
        'end_date',
        'status',
        'rejection_reason',
        'total_impressions',
        'total_clicks',
        'roi_metrics',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sponsorship $sponsorship): void {
            if (empty($sponsorship->uuid)) {
                $sponsorship->uuid = (string) Str::uuid();
            }
            if (empty($sponsorship->contract_number)) {
                $sponsorship->contract_number = 'CEE-SPON-'.date('Y').'-'.strtoupper(Str::random(6));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => SponsorshipStatusEnum::class,
            'target_type' => SponsorshipTargetEnum::class,
            'amount' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'total_impressions' => 'integer',
            'total_clicks' => 'integer',
            'roi_metrics' => 'array',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class, 'brand_profile_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SponsorshipPackage::class, 'package_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(SponsorshipDeliverable::class, 'sponsorship_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(BrandPlacement::class, 'sponsorship_id');
    }
}
