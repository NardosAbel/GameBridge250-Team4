<?php

namespace App\Domain\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvidence extends Model
{
    use HasFactory;

    protected $table = 'match_evidence';

    protected $fillable = [
        'match_result_id',
        'user_id',
        'type', // screenshot, clip, match_id, note
        'url',
        'description',
    ];

    public function matchResult(): BelongsTo
    {
        return $this->belongsTo(MatchResult::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
