<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Venues\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tournament_id',
        'event_id',
        'name',
        'description',
        'category', // free_rsvp, general_admission, vip, student, player_pass, team_pass, staff, press, vendor, early_bird, group, complimentary
        'price',
        'currency',
        'quantity_total',
        'quantity_sold',
        'min_per_order',
        'max_per_order',
        'service_fee',
        'sales_start_at',
        'sales_end_at',
        'settings',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketType $type): void {
            if (empty($type->uuid)) {
                $type->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'quantity_total' => 'integer',
            'quantity_sold' => 'integer',
            'min_per_order' => 'integer',
            'max_per_order' => 'integer',
            'is_active' => 'boolean',
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity_total - $this->quantity_sold);
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->remaining_quantity <= 0) {
            return false;
        }

        $now = now();
        if ($this->sales_start_at && $now->lt($this->sales_start_at)) {
            return false;
        }

        if ($this->sales_end_at && $now->gt($this->sales_end_at)) {
            return false;
        }

        return true;
    }
}
