<?php

namespace App\Domain\Tournaments\Models;

use App\Domain\Profiles\Models\Game;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'game_id',
        'snapshot_date',
        'type', // global, seasonal, university
        'rankings_data',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'rankings_data' => 'array',
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
}
