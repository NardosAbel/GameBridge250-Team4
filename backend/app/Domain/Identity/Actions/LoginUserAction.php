<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    /**
     * Authenticate a user by email or phone and password.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function execute(array $credentials): array
    {
        $login = $credentials['login'];
        $password = $credentials['password'];
        $deviceName = $credentials['device_name'] ?? 'auth-token';

        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->isBanned()) {
            AuditLogger::log('login_blocked_banned', $user, User::class, $user->id);
            throw ValidationException::withMessages([
                'login' => ['This account has been banned due to policy violations.'],
            ]);
        }

        if ($user->isSuspended()) {
            AuditLogger::log('login_blocked_suspended', $user, User::class, $user->id);
            throw ValidationException::withMessages([
                'login' => ['This account is temporarily suspended. Please contact support.'],
            ]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        AuditLogger::log(
            action: 'user_logged_in',
            user: $user,
            entityType: User::class,
            entityId: $user->id,
            newValues: ['device_name' => $deviceName]
        );

        $user->load(['profile', 'roles']);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
