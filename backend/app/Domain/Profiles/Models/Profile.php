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
        'bio',
        'avatar_url',
        'cover_url',
        'university',
        'school_id_number',
        'student_verified',
        'platform_handles',
        'preferred_games',
        'is_public',
    ];

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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
