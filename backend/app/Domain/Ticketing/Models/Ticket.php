<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ticket_order_id',
        'issuance_slot',
        'ticket_type_id',
        'event_id',
        'tournament_id',
        'venue_id',
        'user_id',
        'ticket_code',
        'qr_token_hash',
        'qr_signature',
        'status', // pending_payment, active, checked_in, used, expired, revoked, refunded, transferred
        'checked_in_at',
        'checked_in_by_id',
        'transferred_to_user_id',
        'transfer_history',
        'expires_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (empty($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
            if (empty($ticket->ticket_code)) {
                $ticket->ticket_code = 'ET-'.strtoupper(Str::random(10));
            }
            if (empty($ticket->qr_token_hash)) {
                $ticket->qr_token_hash = hash('sha256', (string) Str::uuid());
            }
        });
    }

    public function getQrPayloadAttribute(): string
    {
        return json_encode([
            'ref' => $this->uuid,
            'sig' => $this->qr_signature,
        ]);
    }

    public function getReferenceNumberAttribute(): string
    {
        return $this->ticket_code;
    }

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'expires_at' => 'datetime',
            'transfer_history' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transferredTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_to_user_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(TicketScan::class);
    }

    public function isValidForEntry(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        return true;
    }
}
