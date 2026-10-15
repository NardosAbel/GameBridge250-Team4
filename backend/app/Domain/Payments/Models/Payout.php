<?php

namespace App\Domain\Payments\Models;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Venues\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'event_id',
        'tournament_id',
        'amount',
        'gross_amount',
        'platform_fee',
        'currency',
        'provider',
        'account_number',
        'status', // pending, processing, paid, rejected
        'notes',
        'processed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payout $payout): void {
            if (empty($payout->uuid)) {
                $payout->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
