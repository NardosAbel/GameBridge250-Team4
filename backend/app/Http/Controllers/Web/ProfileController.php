<?php

namespace App\Http\Controllers\Web;

use App\Domain\Profiles\Actions\UpdateProfileAction;
use App\Domain\Profiles\Services\PlayerPassportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected UpdateProfileAction $updateProfileAction,
        protected PlayerPassportService $playerPassport
    ) {}

    public function show(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'gamer_tag' => 'Gamer_'.substr($user->uuid, 0, 6),
                'is_public' => true,
            ]
        );

        return Inertia::render('Profile/Show', [
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'locale' => $user->locale,
                'roles' => $user->roles->pluck('name'),
                'email_verified' => $user->email_verified_at !== null,
                'phone_verified' => $user->phone_verified_at !== null,
            ],
            'passport' => $this->playerPassport->forUser($user),
            'profile' => [
                'id' => $profile->id,
                'gamer_tag' => $profile->gamer_tag,
                'bio' => $profile->bio,
                'avatar_url' => $profile->avatar_url,
                'cover_url' => $profile->cover_url,
                'university' => $profile->university,
                'school_id_number' => $profile->school_id_number,
                'student_verified' => (bool) $profile->student_verified,
                'platform_handles' => $profile->platform_handles,
                'preferred_games' => $profile->preferred_games,
                'is_public' => (bool) $profile->is_public,
            ],
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->updateProfileAction->execute($request->user(), $request->validated());

        return back()->with('success', 'Profile updated successfully.');
    }
}
