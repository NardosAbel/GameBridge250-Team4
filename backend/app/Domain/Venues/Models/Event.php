<?php

namespace App\Domain\Venues\Models;

use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'venue_id',
        'organizer_id',
        'tournament_id',
        'title',
        'banner_url',
        'description',
        'event_type',
        'start_time',
        'end_time',
        'recurring_pattern',
        'capacity',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            if (empty($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'recurring_pattern' => 'array',
            'capacity' => 'integer',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
