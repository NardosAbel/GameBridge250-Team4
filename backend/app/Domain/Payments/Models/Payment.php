<?php

namespace App\Domain\Payments\Models;

use App\Domain\Ticketing\Models\TicketOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ticket_order_id',
        'user_id',
        'amount',
        'fee',
        'currency',
        'provider', // telebirr, chapa, mpesa, card, bank_transfer, manual
        'provider_reference',
        'status', // pending, paid, failed, cancelled, refunded, partially_refunded
        'idempotency_key',
        'webhook_payload',
        'metadata',
        'paid_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }
            if (empty($payment->idempotency_key)) {
                $payment->idempotency_key = (string) Str::uuid();
            }
            if (empty($payment->user_id) && ! empty($payment->ticket_order_id)) {
                $payment->user_id = TicketOrder::find($payment->ticket_order_id)?->user_id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'webhook_payload' => 'array',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
