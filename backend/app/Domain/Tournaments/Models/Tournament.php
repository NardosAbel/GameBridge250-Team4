<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Profiles\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'organizer_id',
        'game_id',
        'platform',
        'region',
        'visibility',
        'banner_url',
        'logo_url',
        'theme_color',
        'stream_url',
        'venue_id',
        'event_id',
        'format',
        'match_format',
        'event_type',
        'entry_type',
        'entry_fee',
        'currency',
        'prize_pool',
        'capacity',
        'rules',
        'eligibility_rules',
        'points_distribution',
        'settings',
        'registration_start_at',
        'registration_end_at',
        'check_in_start_at',
        'check_in_end_at',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tournament $tournament): void {
            if (empty($tournament->uuid)) {
                $tournament->uuid = (string) Str::uuid();
            }
            if (empty($tournament->slug)) {
                $tournament->slug = Str::slug($tournament->title).'-'.Str::lower(Str::random(6));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'entry_fee' => 'decimal:2',
            'prize_pool' => 'decimal:2',
            'capacity' => 'integer',
            'eligibility_rules' => 'array',
            'points_distribution' => 'array',
            'settings' => 'array',
            'registration_start_at' => 'datetime',
            'registration_end_at' => 'datetime',
            'check_in_start_at' => 'datetime',
            'check_in_end_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function approvedRegistrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class)->where('status', 'approved');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(TournamentStaff::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(Bracket::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function fraudSignals(): HasMany
    {
        return $this->hasMany(TournamentFraudSignal::class);
    }

    public function isRegistrationOpen(): bool
    {
        if ($this->status !== 'registration_open') {
            return false;
        }

        $now = now();
        if ($this->registration_start_at && $now->lt($this->registration_start_at)) {
            return false;
        }

        if ($this->registration_end_at && $now->gt($this->registration_end_at)) {
            return false;
        }

        return true;
    }

    public function isCheckInOpen(): bool
    {
        if (! in_array($this->status, ['check_in', 'registration_closed'], true)) {
            return false;
        }

        $now = now();
        if ($this->check_in_start_at && $now->lt($this->check_in_start_at)) {
            return false;
        }

        if ($this->check_in_end_at && $now->gt($this->check_in_end_at)) {
            return false;
        }

        return true;
    }
}
