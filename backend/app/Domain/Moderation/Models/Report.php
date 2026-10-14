<?php

namespace App\Domain\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status', // pending, reviewed, dismissed, action_taken
        'reviewed_by_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            if (empty($report->uuid)) {
                $report->uuid = (string) Str::uuid();
            }
        });
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function moderationCases(): HasMany
    {
        return $this->hasMany(ModerationCase::class);
    }
}
