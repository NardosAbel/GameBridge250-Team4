<?php

namespace App\Domain\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Otp extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'recipient',
        'code_hash',
        'type',
        'attempts',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    protected static function booted(): void
    {
        static::creating(function (Otp $otp): void {
            if (empty($otp->uuid)) {
                $otp->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture() && $this->attempts < 5;
    }
}
