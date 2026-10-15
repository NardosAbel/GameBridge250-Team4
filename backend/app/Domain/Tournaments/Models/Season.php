<?php

namespace App\Domain\Tournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected static function booted(): void
    {
        static::creating(function (Season $season): void {
            if (empty($season->uuid)) {
                $season->uuid = (string) Str::uuid();
            }
            if (empty($season->slug)) {
                $season->slug = Str::slug($season->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(RankingSnapshot::class);
    }
}
