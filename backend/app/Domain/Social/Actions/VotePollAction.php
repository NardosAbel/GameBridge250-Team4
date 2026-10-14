<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VotePollAction
{
    public function execute(User $user, Post $post, int $optionIndex): Post
    {
        if ($post->type !== 'poll' || empty($post->poll_data)) {
            throw new InvalidArgumentException('This post is not an active poll.');
        }

        return DB::transaction(function () use ($user, $post, $optionIndex) {
            $post = Post::lockForUpdate()->find($post->id);
            $poll = $post->poll_data;

            if (! isset($poll['options'][$optionIndex])) {
                throw new InvalidArgumentException('Invalid poll option selected.');
            }

            $voters = $poll['voters'] ?? [];
            if (isset($voters[$user->id])) {
                throw new InvalidArgumentException('You have already voted in this poll.');
            }

            // Record vote
            $voters[$user->id] = $optionIndex;
            $poll['options'][$optionIndex]['votes'] = ($poll['options'][$optionIndex]['votes'] ?? 0) + 1;
            $poll['voters'] = $voters;

            $post->poll_data = $poll;
            $post->save();

            return $post;
        });
    }
}
