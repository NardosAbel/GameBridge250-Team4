<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TicketScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ticket_id',
        'scanner_user_id',
        'venue_id',
        'event_id',
        'status', // valid, duplicate, invalid, wrong_event, expired, revoked, offline_synced
        'scanned_at',
        'device_id',
        'raw_sync_data',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketScan $scan): void {
            if (empty($scan->uuid)) {
                $scan->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'raw_sync_data' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanner_user_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
