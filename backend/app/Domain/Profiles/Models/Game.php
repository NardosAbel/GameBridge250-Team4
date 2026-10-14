<?php

namespace App\Domain\Profiles\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'banner_url',
        'icon_url',
        'publisher',
        'category',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Game $game): void {
            if (empty($game->uuid)) {
                $game->uuid = (string) Str::uuid();
            }
            if (empty($game->slug)) {
                $game->slug = Str::slug($game->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
