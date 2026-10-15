<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Profiles\Models\Game;
use App\Domain\Teams\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranking extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'game_id',
        'user_id',
        'team_id',
        'points',
        'rating',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'rating' => 'integer',
            'rank' => 'integer',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
