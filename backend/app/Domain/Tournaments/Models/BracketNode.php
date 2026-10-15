<?php

namespace App\Domain\Tournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BracketNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'bracket_id',
        'group_number',
        'round',
        'node_order',
        'next_node_id',
        'loser_next_node_id',
        'is_bye',
    ];

    protected function casts(): array
    {
        return [
            'group_number' => 'integer',
            'round' => 'integer',
            'node_order' => 'integer',
            'next_node_id' => 'integer',
            'loser_next_node_id' => 'integer',
            'is_bye' => 'boolean',
        ];
    }

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(Bracket::class);
    }

    public function nextNode(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_node_id');
    }

    public function loserNextNode(): BelongsTo
    {
        return $this->belongsTo(self::class, 'loser_next_node_id');
    }

    public function match(): HasOne
    {
        return $this->hasOne(TournamentMatch::class, 'bracket_node_id');
    }
}
