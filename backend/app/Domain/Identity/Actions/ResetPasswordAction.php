<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function __construct(
        protected VerifyOtpAction $verifyOtpAction
    ) {}

    /**
     * Reset user password after verifying OTP.
     *
     * @throws ValidationException
     */
    public function execute(string $recipient, string $code, string $newPassword): bool
    {
        $this->verifyOtpAction->execute($recipient, $code, 'password_reset');

        $user = User::where('email', $recipient)
            ->orWhere('phone', $recipient)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'recipient' => ['User not found for this recipient.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        AuditLogger::log(
            action: 'password_reset',
            user: $user,
            entityType: User::class,
            entityId: $user->id
        );

        return true;
    }
}
