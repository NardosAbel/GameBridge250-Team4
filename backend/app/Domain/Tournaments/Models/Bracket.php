<?php

namespace App\Domain\Tournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Bracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tournament_id',
        'name',
        'stage', // group_stage, upper_bracket, lower_bracket, playoffs, main
        'type',  // single_elimination, double_elimination, round_robin, swiss, group_knockout
        'status', // pending, active, completed
        'settings',
    ];

    protected static function booted(): void
    {
        static::creating(function (Bracket $bracket): void {
            if (empty($bracket->uuid)) {
                $bracket->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(BracketNode::class)->orderBy('round')->orderBy('node_order');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }
}
