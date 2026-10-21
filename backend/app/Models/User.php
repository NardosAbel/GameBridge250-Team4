<?php

namespace App\Models;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Identity\Enums\UserStatusEnum;
use App\Domain\Identity\Models\AuditLog;
use App\Domain\Notifications\Models\NotificationPreference;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Social\Models\Follow;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\UserBlock;
use App\Domain\Social\Models\UserMute;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Teams\Models\TeamMember;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'locale',
        'status',
        'suspended_until',
        'muted_until',
        'is_minor',
        'two_factor_enabled',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_minor' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'status' => UserStatusEnum::class,
            'suspended_until' => 'datetime',
            'muted_until' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function communityMembers(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'user_id');
    }

    public function mutedUsers(): HasMany
    {
        return $this->hasMany(UserMute::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatusEnum::ACTIVE;
    }

    public function isBanned(): bool
    {
        return $this->status === UserStatusEnum::BANNED;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatusEnum::SUSPENDED;
    }

    public function isMuted(): bool
    {
        return $this->muted_until !== null && $this->muted_until->isFuture();
    }

    public function brandProfiles(): HasMany
    {
        return $this->hasMany(BrandProfile::class, 'user_id');
    }
}
