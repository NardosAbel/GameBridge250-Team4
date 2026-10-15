<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Teams\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tournament_id',
        'user_id',
        'team_id',
        'status',
        'checked_in',
        'checked_in_at',
        'seed',
        'roster_snapshot',
        'qualification_score',
        'invitation_code',
        'anti_fraud_flags',
        'placement',
        'points_awarded',
    ];

    protected static function booted(): void
    {
        static::creating(function (TournamentRegistration $reg): void {
            if (empty($reg->uuid)) {
                $reg->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'checked_in' => 'boolean',
            'checked_in_at' => 'datetime',
            'seed' => 'integer',
            'roster_snapshot' => 'array',
            'qualification_score' => 'integer',
            'anti_fraud_flags' => 'array',
            'placement' => 'integer',
            'points_awarded' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function fraudSignals(): HasMany
    {
        return $this->hasMany(TournamentFraudSignal::class, 'registration_id');
    }

    public function getParticipantNameAttribute(): string
    {
        if ($this->team_id && $this->team) {
            return $this->team->name;
        }

        if ($this->user_id && $this->user) {
            return $this->user->name;
        }

        return 'Unknown Participant';
    }
}
