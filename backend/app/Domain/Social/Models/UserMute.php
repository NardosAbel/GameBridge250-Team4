<?php

namespace App\Domain\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'muted_user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mutedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'muted_user_id');
    }
}
