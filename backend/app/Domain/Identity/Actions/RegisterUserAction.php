<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Identity\Enums\UserStatusEnum;
use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Profiles\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterUserAction
{
    public function __construct(
        protected SendOtpAction $sendOtpAction
    ) {}

    /**
     * Register a new user with profile and role in a transaction.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string, otp: array}
     */
    public function execute(array $data): array
    {
        $registered = DB::transaction(function () use ($data) {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'locale' => $data['locale'] ?? 'en',
                'status' => UserStatusEnum::ACTIVE,
                'is_minor' => (bool) ($data['is_minor'] ?? false),
            ]);

            // Assign default player role
            $user->assignRole(RoleEnum::PLAYER->value);

            // Create initial gamer profile
            Profile::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'gamer_tag' => $data['gamer_tag'],
                'university' => $data['university'] ?? null,
                'student_verified' => false,
                'is_public' => true,
            ]);

            // Create Sanctum personal access token
            $token = $user->createToken('auth-token')->plainTextToken;

            // Audit log
            AuditLogger::log(
                action: 'user_registered',
                user: $user,
                entityType: User::class,
                entityId: $user->id,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => RoleEnum::PLAYER->value,
                ]
            );

            $user->load(['profile', 'roles']);

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        // OTP delivery can block on SMTP. It runs after the account is committed
        // so a mail outage cannot roll back the user or pin the database transaction.
        $recipient = $registered['user']->email ?? $registered['user']->phone;
        $otpType = $registered['user']->email ? 'email_verification' : 'phone_verification';
        $registered['otp'] = $this->sendOtpAction->execute($recipient, $otpType, $registered['user']);

        return $registered;
    }
}
