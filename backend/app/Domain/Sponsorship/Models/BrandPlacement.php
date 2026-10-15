<?php

namespace App\Domain\Sponsorship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BrandPlacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'brand_profile_id',
        'sponsorship_id',
        'title',
        'placement_type',
        'media_url',
        'destination_url',
        'impressions_count',
        'clicks_count',
        'is_active',
        'start_at',
        'end_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (BrandPlacement $placement): void {
            if (empty($placement->uuid)) {
                $placement->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'impressions_count' => 'integer',
            'clicks_count' => 'integer',
            'is_active' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class, 'brand_profile_id');
    }

    public function sponsorship(): BelongsTo
    {
        return $this->belongsTo(Sponsorship::class);
    }
}
