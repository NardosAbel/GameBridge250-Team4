<?php

namespace App\Domain\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'match_id',
        'raised_by_id',
        'assigned_to_id',
        'reason',
        'status', // open, under_review, resolved, rejected
        'resolution_notes',
        'evidence_urls',
        'action_taken', // overturn_result, rematch, disqualify_participant, confirm_result
        'resolved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Dispute $dispute): void {
            if (empty($dispute->uuid)) {
                $dispute->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'evidence_urls' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }
}
