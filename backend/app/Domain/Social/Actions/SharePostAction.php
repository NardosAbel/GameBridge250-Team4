<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\Share;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SharePostAction
{
    public function execute(User $user, Post $post, ?string $comment = null): Share
    {
        return DB::transaction(function () use ($user, $post, $comment) {
            $share = Share::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'comment' => $comment,
            ]);

            $post->increment('shares_count');

            return $share->load('post');
        });
    }
}
