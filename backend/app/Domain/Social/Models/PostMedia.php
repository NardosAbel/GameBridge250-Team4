<?php

namespace App\Domain\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    use HasFactory;

    protected $table = 'post_media';

    protected $fillable = [
        'post_id',
        'media_url',
        'media_type', // image, video, clip
        'thumbnail_url',
        'order',
        'moderation_status', // approved, pending_review, rejected
        'file_size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
