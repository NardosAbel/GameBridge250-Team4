<?php

namespace Database\Seeders;

use App\Domain\Communities\Models\Community;
use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Identity\Enums\UserStatusEnum;
use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Teams\Models\Team;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Idempotent local demonstration records.
 *
 * Re-running creates nothing that already exists and does not reset a
 * tournament an organizer has since edited. These rows are marked
 * settings.demo so the interface can label them outside production.
 */
class DemoCompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::where('email', 'organizer@campusesports.et')->first();
        if ($organizer === null || ! $organizer->hasRole(RoleEnum::ORGANIZER->value)) {
            return;
        }

        $players = $this->players();
        $this->teams($players);
        $this->communities($organizer);
        $this->venues($organizer);

        $open = [
            [
                'slug' => 'aau-pubg-mobile-open',
                'title' => 'AAU PUBG Mobile Open',
                'game' => 'pubg-mobile',
                'region' => 'Addis Ababa',
                'platform' => 'Mobile',
                'capacity' => 32,
                'starts' => 10,
                'rules' => "Campus students only.\nSolo registration. Bring your own device.\nA no-show at check-in forfeits the slot.",
            ],
            [
                'slug' => 'hawassa-fc25-campus-cup',
                'title' => 'Hawassa FC 25 Campus Cup',
                'game' => 'fc-25',
                'region' => 'Hawassa',
                'platform' => 'Console',
                'capacity' => 16,
                'starts' => 18,
                'rules' => "One player per entry.\nMatches are best of one until the final.\nThe organizer confirms the official result.",
            ],
            [
                'slug' => 'nationwide-valorant-qualifier',
                'title' => 'Nationwide Valorant Qualifier',
                'game' => 'valorant',
                'region' => 'Nationwide',
                'platform' => 'PC',
                'capacity' => 16,
                'starts' => 24,
                'rules' => "Single elimination.\nCaptains submit the score. The organizer confirms it before the bracket advances.",
            ],
        ];

        foreach ($open as $definition) {
            $this->openTournament($organizer, $definition);
        }

        $this->ensureTournament($organizer, [
            'slug' => 'draft-campus-invitational',
            'title' => 'Draft Campus Invitational',
            'game' => 'mlbb',
            'region' => 'Addis Ababa',
            'platform' => 'Mobile',
            'capacity' => 8,
            'status' => 'draft',
            'starts' => 30,
            'rules' => 'Still being written. This draft is visible to the organizer only.',
        ]);

        $pubg = Tournament::where('slug', 'aau-pubg-mobile-open')->first();
        $fc = Tournament::where('slug', 'hawassa-fc25-campus-cup')->first();
        if ($pubg !== null) {
            foreach (array_slice($players, 0, 4) as $index => $player) {
                $this->register($pubg, $player, $index + 1);
            }
        }
        if ($fc !== null && isset($players[4])) {
            $this->register($fc, $players[4], 1);
        }
    }

    /**
     * @return list<User>
     */
    private function players(): array
    {
        $definitions = [
            ['email' => 'player@campusesports.et', 'name' => 'Abebe Gamer', 'tag' => 'HabeshaSniper', 'phone' => '+251911000003', 'university' => 'Addis Ababa University'],
            ['email' => 'demo.meron@example.com', 'name' => 'Meron Desta', 'tag' => 'MeronDesta', 'phone' => '+251911100011', 'university' => 'Addis Ababa University'],
            ['email' => 'demo.yonas@example.com', 'name' => 'Yonas Bekele', 'tag' => 'YonasBekele', 'phone' => '+251911100012', 'university' => 'Hawassa University'],
            ['email' => 'demo.selam@example.com', 'name' => 'Selam Tadesse', 'tag' => 'SelamTadesse', 'phone' => '+251911100013', 'university' => 'Jimma University'],
            ['email' => 'demo.kalkidan@example.com', 'name' => 'Kalkidan Alemu', 'tag' => 'KalkidanAlemu', 'phone' => '+251911100014', 'university' => 'Bahir Dar University'],
        ];

        $players = [];
        foreach ($definitions as $definition) {
            $user = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $definition['name'],
                    'phone' => $definition['phone'],
                    'password' => Hash::make('Password123!'),
                    'locale' => 'en',
                    'status' => UserStatusEnum::ACTIVE,
                    'email_verified_at' => now(),
                ]
            );
            if (! $user->hasRole(RoleEnum::PLAYER->value) && $user->email !== 'player@campusesports.et') {
                $user->assignRole(RoleEnum::PLAYER->value);
            }
            if ($user->email === 'player@campusesports.et' && ! $user->hasRole(RoleEnum::PLAYER->value)) {
                $user->assignRole(RoleEnum::PLAYER->value);
            }

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'gamer_tag' => $definition['tag'],
                    'university' => $definition['university'],
                    'is_public' => true,
                    'student_verified' => false,
                ]
            );

            $players[] = $user;
        }

        return $players;
    }

    /**
     * @param  list<User>  $players
     */
    private function teams(array $players): void
    {
        $pubg = Game::where('slug', 'pubg-mobile')->first();
        $fc = Game::where('slug', 'fc-25')->first();
        if ($pubg === null || $fc === null || ! isset($players[0], $players[2])) {
            return;
        }

        Team::firstOrCreate(
            ['slug' => 'kilo-rifles'],
            [
                'name' => 'Kilo Rifles',
                'tag' => 'KILO',
                'leader_id' => $players[0]->id,
                'game_id' => $pubg->id,
                'university' => 'Addis Ababa University',
                'location' => 'Addis Ababa',
                'recruitment_status' => 'open',
                'bio' => 'Demonstration roster for the AAU PUBG Mobile Open.',
                'is_active' => true,
            ]
        );

        Team::firstOrCreate(
            ['slug' => 'hawassa-green'],
            [
                'name' => 'Hawassa Green',
                'tag' => 'HWGR',
                'leader_id' => $players[2]->id,
                'game_id' => $fc->id,
                'university' => 'Hawassa University',
                'location' => 'Hawassa',
                'recruitment_status' => 'by_application',
                'bio' => 'Demonstration roster for the Hawassa FC 25 Campus Cup.',
                'is_active' => true,
            ]
        );
    }

    private function communities(User $organizer): void
    {
        Community::firstOrCreate(
            ['slug' => 'aau-esports-club'],
            [
                'name' => 'AAU Esports Club',
                'description' => 'Demonstration community for Addis Ababa University players.',
                'type' => 'school',
                'visibility' => 'public',
                'creator_id' => $organizer->id,
            ]
        );
    }

    private function venues(User $organizer): void
    {
        Venue::firstOrCreate(
            ['slug' => 'bole-lan-house'],
            [
                'name' => 'Bole LAN House',
                'description' => 'Demonstration venue. Not a verified partner.',
                'owner_id' => $organizer->id,
                'address' => 'Bole, Addis Ababa',
                'city' => 'Addis Ababa',
                'capacity' => 40,
                'currency' => 'ETB',
                'verified' => false,
                'is_featured' => false,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function openTournament(User $organizer, array $definition): void
    {
        $this->ensureTournament($organizer, $definition + ['status' => 'registration_open']);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function ensureTournament(User $organizer, array $definition): void
    {
        $existing = Tournament::withTrashed()->where('slug', $definition['slug'])->first();
        if ($existing !== null) {
            return;
        }

        $game = Game::where('slug', $definition['game'])->first();
        if ($game === null) {
            return;
        }

        $tournament = app(CreateTournamentAction::class)->execute($organizer, [
            'title' => $definition['title'],
            'game_id' => $game->id,
            'platform' => $definition['platform'],
            'region' => $definition['region'],
            'format' => 'single_elimination',
            'match_format' => 'bo1',
            'event_type' => 'hybrid',
            'entry_type' => 'free',
            'entry_fee' => 0,
            'prize_pool' => 0,
            'capacity' => $definition['capacity'],
            'rules' => $definition['rules'],
            'visibility' => 'public',
            'currency' => 'ETB',
            'registration_start_at' => now()->subDay(),
            'registration_end_at' => now()->addDays((int) $definition['starts'] - 1),
            'starts_at' => now()->addDays((int) $definition['starts']),
            'ends_at' => now()->addDays((int) $definition['starts'] + 1),
        ]);

        $tournament->forceFill([
            'slug' => $definition['slug'],
            'status' => $definition['status'],
            'settings' => array_merge($tournament->settings ?? [], [
                'demo' => true,
                'third_place_match' => false,
                'auto_advance_byes' => true,
            ]),
        ])->save();
    }

    private function register(Tournament $tournament, User $player, int $seed): void
    {
        TournamentRegistration::firstOrCreate(
            [
                'tournament_id' => $tournament->id,
                'user_id' => $player->id,
            ],
            [
                'status' => 'approved',
                'seed' => $seed,
            ]
        );
    }
}
