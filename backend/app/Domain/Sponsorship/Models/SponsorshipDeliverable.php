<?php

namespace App\Domain\Sponsorship\Models;

use App\Domain\Sponsorship\Enums\DeliverableStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SponsorshipDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'sponsorship_id',
        'title',
        'description',
        'deliverable_type',
        'due_date',
        'evidence_url',
        'evidence_notes',
        'status',
        'verified_by_id',
        'verified_at',
        'rejection_notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (SponsorshipDeliverable $deliverable): void {
            if (empty($deliverable->uuid)) {
                $deliverable->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => DeliverableStatusEnum::class,
            'due_date' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function sponsorship(): BelongsTo
    {
        return $this->belongsTo(Sponsorship::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }
}
