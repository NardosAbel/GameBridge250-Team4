<?php

namespace App\Domain\Venues\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueStaff extends Model
{
    use HasFactory;

    protected $table = 'venue_staff';

    protected $fillable = [
        'venue_id',
        'user_id',
        'role', // manager, scanner
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
