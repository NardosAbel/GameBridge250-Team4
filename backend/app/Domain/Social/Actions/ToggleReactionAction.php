<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ToggleReactionAction
{
    public function execute(User $user, Model $reactable, string $type = 'like'): array
    {
        $validTypes = ['like', 'fire', 'trophy', 'gg'];
        if (! in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Invalid reaction type: {$type}");
        }

        $existing = Reaction::where('user_id', $user->id)
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->first();

        if ($existing) {
            if ($existing->type === $type) {
                $existing->delete();

                return ['status' => 'removed', 'reaction' => null];
            } else {
                $existing->update(['type' => $type]);

                return ['status' => 'updated', 'reaction' => $existing];
            }
        }

        $reaction = Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => $reactable->getMorphClass(),
            'reactable_id' => $reactable->getKey(),
            'type' => $type,
        ]);

        return ['status' => 'created', 'reaction' => $reaction];
    }
}
