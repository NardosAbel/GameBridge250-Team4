<?php

namespace App\Domain\Sponsorship\Models;

use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PrizePoolContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tournament_id',
        'sponsor_id',
        'brand_profile_id',
        'amount',
        'currency',
        'donor_name',
        'is_anonymous',
        'message',
        'status',
        'paid_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrizePoolContribution $contribution): void {
            if (empty($contribution->uuid)) {
                $contribution->uuid = (string) Str::uuid();
            }
            if (empty($contribution->paid_at)) {
                $contribution->paid_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class, 'brand_profile_id');
    }
}
