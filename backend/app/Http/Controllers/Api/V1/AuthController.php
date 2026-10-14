<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\LoginUserAction;
use App\Domain\Identity\Actions\RegisterUserAction;
use App\Domain\Identity\Actions\ResetPasswordAction;
use App\Domain\Identity\Actions\SendOtpAction;
use App\Domain\Identity\Actions\VerifyOtpAction;
use App\Domain\Identity\Services\AuditLogger;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        protected RegisterUserAction $registerUserAction,
        protected LoginUserAction $loginUserAction,
        protected SendOtpAction $sendOtpAction,
        protected VerifyOtpAction $verifyOtpAction,
        protected ResetPasswordAction $resetPasswordAction,
    ) {}

    /**
     * Register a new user account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->registerUserAction->execute($request->validated());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'otp' => $result['otp'],
        ], 'User registered successfully. Verification OTP dispatched.', 201);
    }

    /**
     * Log in with credentials and receive a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginUserAction->execute($request->validated());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully.');
    }

    /**
     * Send or resend an OTP code.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->recipient)
            ->orWhere('phone', $request->recipient)
            ->first();

        $otp = $this->sendOtpAction->execute(
            $request->recipient,
            $request->type,
            $user
        );

        return $this->successResponse($otp, 'Verification code sent successfully.');
    }

    /**
     * Verify an OTP code for email or phone.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $this->verifyOtpAction->execute(
            $request->recipient,
            $request->code,
            $request->type
        );

        return $this->successResponse([
            'verified' => true,
            'recipient' => $request->recipient,
            'type' => $request->type,
        ], 'Verification successful.');
    }

    /**
     * Reset password using verified OTP code.
     */
    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $this->resetPasswordAction->execute(
            $request->recipient,
            $request->code,
            $request->password
        );

        return $this->successResponse([], 'Password reset successfully. Please log in with your new password.');
    }

    /**
     * Get the authenticated user with roles and profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile', 'roles', 'permissions']);

        return $this->successResponse(new UserResource($user), 'Authenticated user retrieved.');
    }

    /**
     * Log out and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
            AuditLogger::log('user_logged_out', $user, User::class, $user->id);
        }

        return $this->successResponse([], 'Logged out successfully.');
    }
}
