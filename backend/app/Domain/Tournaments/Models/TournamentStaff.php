<?php

namespace App\Domain\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentStaff extends Model
{
    use HasFactory;

    protected $table = 'tournament_staff';

    protected $fillable = [
        'tournament_id',
        'user_id',
        'role', // admin, co_organizer, referee, scorekeeper, caster, observer, support
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
