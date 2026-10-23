<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        // Email OTP codes are the supported delivery channel. The code is short-lived
        // (10 minutes), single-use, and rate-limited. A signed magic-link alternative
        // is a separate product change; do not put the OTP in verification URLs.
        $channel = filter_var($recipient, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $delivered = false;
        if ($channel === 'email') {
            try {
                Mail::raw(
                    "Your Campus Esports Ethiopia verification code is {$plainOtp}. It expires in 10 minutes.",
                    function ($message) use ($recipient): void {
                        $message->to($recipient)->subject('Your verification code');
                    }
                );
                $delivered = true;
            } catch (\Throwable) {
                Log::warning('Verification email could not be sent.', [
                    'otp_id' => $otp->uuid,
                    'type' => $type,
                ]);
            }
        } else {
            // OTP row is stored either way. No SMS provider is wired yet — do not
            // invent delivery. Local/testing still return the code in the response.
            Log::info('SMS OTP provider is not configured; verification code was stored but not delivered.', [
                'otp_id' => $otp->uuid,
                'type' => $type,
                'channel' => 'phone',
            ]);
        }

        return [
            'otp_id' => $otp->uuid,
            'recipient' => $recipient,
            'type' => $type,
            'expires_in_seconds' => 600,
            'delivery' => $channel === 'email'
                ? ($delivered ? 'email' : 'email_failed')
                : 'pending_sms_provider',
            // Exposed only in local and testing environments
            'code' => app()->environment(['local', 'testing']) ? $plainOtp : null,
        ];
    }
}
