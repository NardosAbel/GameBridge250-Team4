<?php

namespace App\Domain\Social\Services;

use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\PostMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MediaUploadService
{
    protected const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    protected const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];

    protected const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB

    protected const MAX_VIDEO_SIZE = 50 * 1024 * 1024; // 50MB

    /**
     * Upload and attach media files to a post.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, PostMedia>
     */
    public function uploadPostMedia(Post $post, array $files): array
    {
        $mediaRecords = [];
        $order = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            if (in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true)) {
                if ($fileSize > self::MAX_IMAGE_SIZE) {
                    throw new InvalidArgumentException("Image {$file->getClientOriginalName()} exceeds maximum allowed size of 5MB.");
                }
                $mediaType = 'image';
            } elseif (in_array($mimeType, self::ALLOWED_VIDEO_TYPES, true)) {
                if ($fileSize > self::MAX_VIDEO_SIZE) {
                    throw new InvalidArgumentException("Video {$file->getClientOriginalName()} exceeds maximum allowed size of 50MB.");
                }
                $mediaType = 'video';
            } else {
                throw new InvalidArgumentException("Unsupported media type: {$mimeType}");
            }

            // Store to disk (S3-compatible abstraction via Laravel default disk)
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs("posts/{$post->id}", $filename, 'public');
            $url = Storage::disk('public')->url($path);

            $thumbnailUrl = $mediaType === 'image' ? $url : null;

            $postMedia = PostMedia::create([
                'post_id' => $post->id,
                'media_url' => $url,
                'media_type' => $mediaType,
                'thumbnail_url' => $thumbnailUrl,
                'order' => $order++,
                'moderation_status' => 'approved', // Auto-approved unless flagged by moderation rules
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
            ]);

            $mediaRecords[] = $postMedia;
        }

        return $mediaRecords;
    }
}
