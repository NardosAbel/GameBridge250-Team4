<?php

namespace App\Domain\Tournaments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRankingAward extends Model
{
    protected $fillable = [
        'tournament_id',
        'ranking_id',
        'points',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class);
    }
}
