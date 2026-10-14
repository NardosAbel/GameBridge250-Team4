<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Bookmark;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ToggleBookmarkAction
{
    public function execute(User $user, Model $bookmarkable): array
    {
        $existing = Bookmark::where('user_id', $user->id)
            ->where('bookmarkable_type', $bookmarkable->getMorphClass())
            ->where('bookmarkable_id', $bookmarkable->getKey())
            ->first();

        if ($existing) {
            $existing->delete();

            return ['status' => 'unbookmarked', 'is_bookmarked' => false];
        }

        Bookmark::create([
            'user_id' => $user->id,
            'bookmarkable_type' => $bookmarkable->getMorphClass(),
            'bookmarkable_id' => $bookmarkable->getKey(),
        ]);

        return ['status' => 'bookmarked', 'is_bookmarked' => true];
    }
}
