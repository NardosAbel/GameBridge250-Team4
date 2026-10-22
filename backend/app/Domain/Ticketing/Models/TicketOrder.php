<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Payments\Models\Payment;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Venues\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'event_id',
        'tournament_id',
        'total_amount',
        'subtotal',
        'discount_amount',
        'service_fee',
        'currency',
        'status', // pending, completed, failed, cancelled, refunded, partially_refunded
        'idempotency_key',
        'payment_method', // telebirr, chapa, mpesa, card, bank_transfer, manual
        'promo_code_id',
        'billing_details',
        'notes',
        'inventory_released_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketOrder $order): void {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
            if (empty($order->idempotency_key)) {
                $order->idempotency_key = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'billing_details' => 'array',
            'inventory_released_at' => 'datetime',
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

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TicketOrderItem::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
