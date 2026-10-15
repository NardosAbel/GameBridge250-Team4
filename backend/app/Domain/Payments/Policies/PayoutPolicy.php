<?php

namespace App\Domain\Payments\Policies;

use App\Domain\Payments\Models\Payout;
use App\Models\User;

class PayoutPolicy
{
    public function view(User $user, Payout $payout): bool
    {
        return $payout->user_id === $user->id || $user->hasRole('super-admin');
    }

    public function process(User $user, Payout $payout): bool
    {
        return $user->hasRole('super-admin');
    }
}
