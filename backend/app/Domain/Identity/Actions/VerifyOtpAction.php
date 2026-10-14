<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Otp;
use App\Domain\Identity\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyOtpAction
{
    /**
     * Verify an OTP code.
     *
     * @throws ValidationException
     */
    public function execute(string $recipient, string $code, string $type): bool
    {
        $otp = Otp::where('recipient', $recipient)
            ->where('type', $type)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => ['No active verification code found for this recipient.'],
            ]);
        }

        if ($otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['This verification code has expired. Please request a new one.'],
            ]);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Too many failed attempts. This code is now invalidated.'],
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        $otp->update(['used_at' => now()]);

        // If user is matched, update verified timestamp
        $user = User::where('email', $recipient)
            ->orWhere('phone', $recipient)
            ->first();

        if ($user) {
            if ($type === 'email_verification' && ! $user->email_verified_at) {
                $user->update(['email_verified_at' => now()]);
                AuditLogger::log('email_verified', $user, User::class, $user->id);
            } elseif ($type === 'phone_verification' && ! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
                AuditLogger::log('phone_verified', $user, User::class, $user->id);
            }
        }

        return true;
    }
}
