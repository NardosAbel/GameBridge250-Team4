<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config('app.name', 'Campus Esports Ethiopia'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'locale' => $user->locale,
                    'status' => $user->status instanceof \BackedEnum ? $user->status->value : (string) $user->status,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'profile' => $user->profile ? [
                        'gamer_tag' => $user->profile->gamer_tag,
                        'avatar_url' => $user->profile->avatar_url,
                        'university' => $user->profile->university,
                        'student_verified' => $user->profile->student_verified,
                    ] : null,
                ] : null,
            ],
            'locale' => $user?->locale ?? session('locale', config('app.locale', 'en')),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
