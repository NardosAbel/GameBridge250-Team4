<?php

namespace App\Domain\Chat\Models;

use App\Domain\Communities\Models\Community;
use App\Domain\Teams\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type', // team, community, direct
        'team_id',
        'community_id',
        'name',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChatChannel $channel): void {
            if (empty($channel->uuid)) {
                $channel->uuid = (string) Str::uuid();
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatChannelMember::class, 'channel_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')->latest();
    }
}
