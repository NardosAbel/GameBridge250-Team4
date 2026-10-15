<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Teams\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'uuid',
        'tournament_id',
        'bracket_node_id',
        'match_format', // bo1, bo3, bo5
        'round',
        'match_number',
        'participant1_id',
        'participant2_id',
        'participant1_type', // user, team
        'participant2_type',
        'participant1_checked_in',
        'participant2_checked_in',
        'participant1_ready',
        'participant2_ready',
        'winner_id',
        'status', // pending, ready, live, completed, disputed, forfeited
        'participant1_score',
        'participant2_score',
        'room_id',
        'room_password',
        'room_server',
        'room_instructions',
        'referee_id',
        'is_bye',
        'scheduled_at',
        'completed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (TournamentMatch $match): void {
            if (empty($match->uuid)) {
                $match->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'match_number' => 'integer',
            'participant1_id' => 'integer',
            'participant2_id' => 'integer',
            'winner_id' => 'integer',
            'participant1_score' => 'integer',
            'participant2_score' => 'integer',
            'participant1_checked_in' => 'boolean',
            'participant2_checked_in' => 'boolean',
            'participant1_ready' => 'boolean',
            'participant2_ready' => 'boolean',
            'is_bye' => 'boolean',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function bracketNode(): BelongsTo
    {
        return $this->belongsTo(BracketNode::class, 'bracket_node_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(MatchResult::class, 'match_id')->latest();
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(MatchResult::class, 'match_id')->latestOfMany();
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'match_id')->latest();
    }

    public function activeDispute(): HasOne
    {
        return $this->hasOne(Dispute::class, 'match_id')->whereIn('status', ['open', 'under_review'])->latestOfMany();
    }

    public function getParticipant1EntityAttribute(): User|Team|null
    {
        if (! $this->participant1_id) {
            return null;
        }

        return $this->participant1_type === 'team'
            ? Team::find($this->participant1_id)
            : User::find($this->participant1_id);
    }

    public function getParticipant2EntityAttribute(): User|Team|null
    {
        if (! $this->participant2_id) {
            return null;
        }

        return $this->participant2_type === 'team'
            ? Team::find($this->participant2_id)
            : User::find($this->participant2_id);
    }

    public function getParticipant1NameAttribute(): string
    {
        if ($this->is_bye && ! $this->participant1_id) {
            return 'BYE';
        }

        $entity = $this->participant1_entity;

        return $entity ? $entity->name : 'TBD';
    }

    public function getParticipant2NameAttribute(): string
    {
        if ($this->is_bye && ! $this->participant2_id) {
            return 'BYE';
        }

        $entity = $this->participant2_entity;

        return $entity ? $entity->name : 'TBD';
    }

    public function getScoreToWin(): int
    {
        return match ($this->match_format) {
            'bo3' => 2,
            'bo5' => 3,
            default => 1,
        };
    }
}
