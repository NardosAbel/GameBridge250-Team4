<?php

namespace App\Domain\Payments\Policies;

use App\Domain\Payments\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $payment->user_id === $user->id
            || ($payment->order && $payment->order->event && $payment->order->event->organizer_id === $user->id)
            || $user->hasRole('super_admin');
    }
}
