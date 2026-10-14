<?php

namespace App\Domain\Teams\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'team_id',
        'user_id',
        'type', // invitation, join_request
        'role',
        'status', // pending, accepted, declined, cancelled
        'invited_by_id',
        'message',
    ];

    protected static function booted(): void
    {
        static::creating(function (TeamInvitation $invitation): void {
            if (empty($invitation->uuid)) {
                $invitation->uuid = (string) Str::uuid();
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }
}
