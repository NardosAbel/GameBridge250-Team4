<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Profiles\Actions\UpdateProfileAction;
use App\Domain\Profiles\Models\Profile;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProfileController extends ApiController
{
    public function __construct(
        protected UpdateProfileAction $updateProfileAction
    ) {}

    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'gamer_tag' => 'Gamer_'.substr($user->uuid, 0, 6),
                'is_public' => true,
            ]
        );

        return $this->successResponse(new ProfileResource($profile), 'Profile retrieved successfully.');
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $profile = $this->updateProfileAction->execute($request->user(), $request->validated());

        return $this->successResponse(new ProfileResource($profile), 'Profile updated successfully.');
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'gamer_tag' => 'Gamer_'.substr($user->uuid, 0, 6),
                'is_public' => true,
            ]
        );

        $path = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        $profile->update([
            'avatar_url' => Storage::disk('public')->url($path),
        ]);

        return $this->successResponse(new ProfileResource($profile->fresh()), 'Profile picture updated.');
    }

    /**
     * Get public profile by gamer tag.
     */
    public function getByGamerTag(Request $request, string $gamerTag): JsonResponse
    {
        $profile = Profile::where('gamer_tag', $gamerTag)->first();

        if (! $profile || Gate::forUser($request->user())->denies('view', $profile)) {
            return $this->errorResponse('Gamer profile not found or is set to private.', [], 404);
        }

        return $this->successResponse(new ProfileResource($profile), 'Public gamer profile retrieved.');
    }
}
