<?php

namespace App\Domain\Recruitment\Models;

use App\Domain\Profiles\Models\Game;
use App\Domain\Teams\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RecruitmentPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'team_id',
        'game_id',
        'type', // lft (looking for team), lfp (looking for players)
        'title',
        'description',
        'rank_tier',
        'role',
        'location',
        'university',
        'age_group',
        'availability',
        'platform',
        'status', // open, closed
    ];

    protected static function booted(): void
    {
        static::creating(function (RecruitmentPost $post): void {
            if (empty($post->uuid)) {
                $post->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
