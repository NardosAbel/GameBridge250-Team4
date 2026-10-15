<?php

namespace App\Domain\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentFraudSignal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'registration_id',
        'user_id',
        'signal_type', // duplicate_ip, duplicate_student_id, multi_team_roster, device_fingerprint_match, banned_player_smurf
        'severity',    // low, medium, high, critical
        'metadata',
        'resolved',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'resolved' => 'boolean',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
