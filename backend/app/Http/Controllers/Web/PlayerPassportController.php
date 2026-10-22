<?php

namespace App\Http\Controllers\Web;

use App\Domain\Profiles\Models\Profile;
use App\Domain\Profiles\Services\PlayerPassportService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PlayerPassportController extends Controller
{
    public function __construct(
        protected PlayerPassportService $passport
    ) {}

    public function show(string $handle): Response
    {
        $profile = Profile::query()
            ->where('gamer_tag', $handle)
            ->with('user')
            ->firstOrFail();

        abort_unless($profile->isVisibleTo(request()->user()), 404);

        return Inertia::render('Profile/Passport', [
            'player' => [
                'name' => $profile->user?->name,
                'gamer_tag' => $profile->gamer_tag,
                'university' => $profile->university,
                'bio' => $profile->bio,
                'student_verified' => (bool) $profile->student_verified,
            ],
            'passport' => $this->passport->forUser($profile->user),
        ]);
    }
}
