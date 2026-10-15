<?php

namespace App\Domain\Venues\Models;

use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Venue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'owner_id',
        'address',
        'city',
        'latitude',
        'longitude',
        'map_link',
        'facilities',
        'opening_hours',
        'contact_details',
        'rules',
        'capacity',
        'hourly_rate',
        'pricing_details',
        'currency',
        'verified',
        'is_featured',
        'photos',
    ];

    protected static function booted(): void
    {
        static::creating(function (Venue $venue): void {
            if (empty($venue->uuid)) {
                $venue->uuid = (string) Str::uuid();
            }
            if (empty($venue->slug)) {
                $venue->slug = Str::slug($venue->name).'-'.Str::random(5);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'facilities' => 'array',
            'opening_hours' => 'array',
            'contact_details' => 'array',
            'pricing_details' => 'array',
            'photos' => 'array',
            'verified' => 'boolean',
            'is_featured' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'hourly_rate' => 'decimal:2',
            'capacity' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(VenueStaff::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class, 'venue_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VenueReview::class);
    }

    public function getAverageRatingAttribute(): float
    {
        return (float) ($this->reviews()->avg('rating') ?? 0.0);
    }

    public function getReviewsCountAttribute(): int
    {
        return (int) $this->reviews()->count();
    }

    public function isStaff(User $user, ?string $role = null): bool
    {
        if ($this->owner_id === $user->id || $user->hasRole('super-admin')) {
            return true;
        }

        $query = $this->staff()->where('user_id', $user->id);
        if ($role) {
            $query->where('role', $role);
        }

        return $query->exists();
    }
}
