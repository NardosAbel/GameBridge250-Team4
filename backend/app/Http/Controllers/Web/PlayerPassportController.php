<?php

namespace App\Http\Controllers\Web;

use App\Domain\Profiles\Models\Profile;
use App\Domain\Profiles\Services\PlayerPassportService;
use App\Domain\Social\Models\Follow;
use App\Domain\Social\Models\Post;
use App\Http\Controllers\Controller;
use App\Models\User;
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

        $viewer = request()->user();
        $posts = Post::query()
            ->where('user_id', $profile->user_id)
            ->where('visibility', 'public')
            ->latest()
            ->limit(8)
            ->get(['id', 'content', 'created_at', 'tournament_id', 'sponsored']);

        return Inertia::render('Profile/Passport', [
            'player' => [
                'id' => $profile->user_id,
                'name' => $profile->user?->name,
                'gamer_tag' => $profile->gamer_tag,
                'avatar_url' => $profile->avatar_url,
                'university' => $profile->university,
                'bio' => $profile->bio,
                'student_verified' => (bool) $profile->student_verified,
            ],
            'social' => [
                'followers_count' => Follow::query()
                    ->where('followable_type', User::class)
                    ->where('followable_id', $profile->user_id)
                    ->count(),
                'viewer_following' => $viewer ? Follow::query()
                    ->where('follower_id', $viewer->id)
                    ->where('followable_type', User::class)
                    ->where('followable_id', $profile->user_id)
                    ->exists() : false,
                'posts' => $posts->map(fn (Post $post) => [
                    'id' => $post->id,
                    'content' => $post->content,
                    'sponsored' => (bool) $post->sponsored,
                    'created_at' => $post->created_at?->toISOString(),
                ])->all(),
            ],
            'passport' => $this->passport->forUser($profile->user),
        ]);
    }
}
