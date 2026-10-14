<?php

namespace App\Http\Controllers\Web;

use App\Domain\Identity\Actions\LoginUserAction;
use App\Domain\Identity\Actions\RegisterUserAction;
use App\Domain\Identity\Actions\ResetPasswordAction;
use App\Domain\Identity\Actions\SendOtpAction;
use App\Domain\Identity\Actions\VerifyOtpAction;
use App\Domain\Identity\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        protected RegisterUserAction $registerUserAction,
        protected LoginUserAction $loginUserAction,
        protected SendOtpAction $sendOtpAction,
        protected VerifyOtpAction $verifyOtpAction,
        protected ResetPasswordAction $resetPasswordAction,
    ) {}

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $result = $this->loginUserAction->execute($request->validated());

        Auth::login($result['user'], (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/dashboard')->with('success', 'Welcome back!');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $result = $this->registerUserAction->execute($request->validated());

        Auth::login($result['user']);
        $request->session()->regenerate();

        $recipient = $result['user']->email ?? $result['user']->phone;
        $otpType = $result['user']->email ? 'email_verification' : 'phone_verification';

        return redirect()->route('verification.notice', [
            'recipient' => $recipient,
            'type' => $otpType,
            'devCode' => $result['otp']['code'] ?? null,
        ])->with('success', 'Registration successful! Please verify your account.');
    }

    public function showVerifyOtp(Request $request): Response
    {
        return Inertia::render('Auth/VerifyOtp', [
            'recipient' => $request->query('recipient', $request->user()?->email ?? $request->user()?->phone),
            'type' => $request->query('type', 'email_verification'),
            'devCode' => $request->query('devCode'),
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $this->verifyOtpAction->execute(
            $request->recipient,
            $request->code,
            $request->type
        );

        return redirect()->route('dashboard')->with('success', 'Account verified successfully!');
    }

    public function resendOtp(SendOtpRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->recipient)
            ->orWhere('phone', $request->recipient)
            ->first();

        $otp = $this->sendOtpAction->execute(
            $request->recipient,
            $request->type,
            $user
        );

        return back()->with([
            'success' => 'A new 6-digit verification code has been dispatched.',
            'devCode' => $otp['code'] ?? null,
        ]);
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function forgotPassword(SendOtpRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->recipient)
            ->orWhere('phone', $request->recipient)
            ->first();

        $otp = $this->sendOtpAction->execute(
            $request->recipient,
            'password_reset',
            $user
        );

        return redirect()->route('password.reset', [
            'recipient' => $request->recipient,
        ])->with([
            'success' => 'Password reset code dispatched.',
            'devCode' => $otp['code'] ?? null,
        ]);
    }

    public function showResetPassword(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'recipient' => $request->query('recipient', ''),
        ]);
    }

    public function resetPassword(PasswordResetRequest $request): RedirectResponse
    {
        $this->resetPasswordAction->execute(
            $request->recipient,
            $request->code,
            $request->password
        );

        return redirect()->route('login')->with('success', 'Password reset successfully. Please log in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            AuditLogger::log('user_logged_out', $user, User::class, $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Logged out successfully.');
    }
}
