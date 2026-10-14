<?php

namespace App\Domain\Teams\Models;

use App\Domain\Profiles\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'tag',
        'slug',
        'bio',
        'logo_url',
        'banner_url',
        'leader_id',
        'game_id',
        'university',
        'location',
        'recruitment_status', // open, closed, by_application
        'achievements',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team): void {
            if (empty($team->uuid)) {
                $team->uuid = (string) Str::uuid();
            }
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'achievements' => 'array',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class)->where('status', 'active');
    }

    public function rosterHistory(): HasMany
    {
        return $this->hasMany(TeamRosterHistory::class)->latest();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class)->latest();
    }
}
