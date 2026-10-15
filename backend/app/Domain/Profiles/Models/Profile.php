<?php

namespace App\Domain\Profiles\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'gamer_tag',
        'handle',
        'bio',
        'avatar_url',
        'cover_url',
        'banner_url',
        'university',
        'location',
        'school_id_number',
        'student_id_number',
        'student_verified',
        'platform_handles',
        'preferred_games',
        'roles',
        'platforms',
        'availability',
        'is_public',
        'privacy_settings',
        'achievements',
        'badges',
    ];

    public function setStudentIdNumberAttribute(?string $value): void
    {
        $this->attributes['school_id_number'] = $value;
    }

    public function getStudentIdNumberAttribute(): ?string
    {
        return $this->attributes['school_id_number'] ?? null;
    }

    protected static function booted(): void
    {
        static::creating(function (Profile $profile): void {
            if (empty($profile->uuid)) {
                $profile->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'student_verified' => 'boolean',
            'is_public' => 'boolean',
            'platform_handles' => 'array',
            'preferred_games' => 'array',
            'roles' => 'array',
            'platforms' => 'array',
            'availability' => 'array',
            'privacy_settings' => 'array',
            'achievements' => 'array',
            'badges' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
