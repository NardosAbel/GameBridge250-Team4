<?php

namespace App\Domain\Education\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'course_id',
        'certificate_number',
        'verification_hash',
        'verification_code',
        'issued_at',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate): void {
            if (empty($certificate->uuid)) {
                $certificate->uuid = (string) Str::uuid();
            }
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = 'CEE-CERT-'.date('Y').'-'.strtoupper(Str::random(8));
            }
            if (empty($certificate->verification_code)) {
                $certificate->verification_code = 'CEE-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
            }
            if (empty($certificate->verification_hash)) {
                $certificate->verification_hash = hash('sha256', $certificate->certificate_number.$certificate->user_id.now()->timestamp);
            }
            if (empty($certificate->issued_at)) {
                $certificate->issued_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
