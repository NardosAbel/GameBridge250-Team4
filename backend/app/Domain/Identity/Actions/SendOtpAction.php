<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class SendOtpAction
{
    /**
     * Generate and dispatch an OTP code.
     * Returns the plain OTP in development/testing, or sends via SMS/email.
     */
    public function execute(string $recipient, string $type, ?User $user = null): array
    {
        // In local development or testing, use deterministic or easily readable 6-digit OTP
        $plainOtp = app()->environment(['local', 'testing']) ? '123456' : (string) random_int(100000, 999999);

        // Invalidate any prior active OTPs for this recipient & type
        Otp::where('recipient', $recipient)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = Otp::create([
            'user_id' => $user?->id,
            'recipient' => $recipient,
            'code_hash' => Hash::make($plainOtp),
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => Request::ip(),
        ]);

        return [
            'otp_id' => $otp->uuid,
            'recipient' => $recipient,
            'type' => $type,
            'expires_in_seconds' => 600,
            // Exposed only in local and testing environments
            'code' => app()->environment(['local', 'testing']) ? $plainOtp : null,
        ];
    }
}
