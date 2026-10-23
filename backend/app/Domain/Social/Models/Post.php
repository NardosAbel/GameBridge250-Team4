<?php

namespace App\Domain\Social\Models;

use App\Domain\Communities\Models\Community;
use App\Domain\Profiles\Models\Game;
use App\Domain\Teams\Models\Team;
use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'community_id',
        'team_id',
        'game_id',
        'tournament_id',
        'title',
        'content',
        'type', // text, clip, livestream, announcement, poll
        'location_tag',
        'hashtags',
        'poll_data',
        'visibility', // public, community, team
        'pinned',
        'sponsored',
        'shares_count',
        'views_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            if (empty($post->uuid)) {
                $post->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
            'poll_data' => 'array',
            'pinned' => 'boolean',
            'sponsored' => 'boolean',
            'shares_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }
}
