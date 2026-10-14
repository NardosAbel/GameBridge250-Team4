<?php

namespace App\Domain\Communities\Models;

use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Community extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'type', // school, game, city, venue, interest, creator
        'visibility', // public, private, approval
        'creator_id',
        'avatar_url',
        'banner_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Community $community): void {
            if (empty($community->uuid)) {
                $community->uuid = (string) Str::uuid();
            }
            if (empty($community->slug)) {
                $community->slug = Str::slug($community->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(CommunityMember::class)->where('status', 'active');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->latest();
    }
}
