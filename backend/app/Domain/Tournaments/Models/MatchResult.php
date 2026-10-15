<?php

namespace App\Domain\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'submitted_by_id',
        'confirmed_by_id',
        'participant1_score',
        'participant2_score',
        'status', // submitted, confirmed, disputed, overridden
        'notes',
        'game_metadata',
    ];

    protected function casts(): array
    {
        return [
            'participant1_score' => 'integer',
            'participant2_score' => 'integer',
            'game_metadata' => 'array',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(MatchEvidence::class, 'match_result_id');
    }
}
