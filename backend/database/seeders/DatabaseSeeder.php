<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Identity\Enums\UserStatusEnum;
use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // 1. Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@campusesports.et'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'System Super Admin',
                'phone' => '+251911000001',
                'password' => Hash::make('Password123!'),
                'locale' => 'en',
                'status' => UserStatusEnum::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $admin->syncRoles([RoleEnum::SUPER_ADMIN->value]);

        Profile::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'gamer_tag' => 'SuperAdmin',
                'bio' => 'Campus Esports Ethiopia Super Administrator',
                'university' => 'Addis Ababa University',
                'student_verified' => true,
            ]
        );

        // 2. Organizer
        $organizer = User::firstOrCreate(
            ['email' => 'organizer@campusesports.et'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Campus League Organizer',
                'phone' => '+251911000002',
                'password' => Hash::make('Password123!'),
                'locale' => 'en',
                'status' => UserStatusEnum::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $organizer->syncRoles([RoleEnum::ORGANIZER->value]);

        Profile::firstOrCreate(
            ['user_id' => $organizer->id],
            [
                'gamer_tag' => 'TournamentDirector',
                'bio' => 'Official Campus Esports Tournament Director',
                'university' => 'Addis Ababa Science and Technology University',
                'student_verified' => true,
            ]
        );

        // 3. Competitive Player
        $player = User::firstOrCreate(
            ['email' => 'player@campusesports.et'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Abebe Gamer',
                'phone' => '+251911000003',
                'password' => Hash::make('Password123!'),
                'locale' => 'am',
                'status' => UserStatusEnum::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $player->syncRoles([RoleEnum::PLAYER->value]);

        Profile::firstOrCreate(
            ['user_id' => $player->id],
            [
                'gamer_tag' => 'HabeshaSniper',
                'bio' => 'CS:GO & PUBG Mobile competitive player at AAU 4 Kilo',
                'university' => 'Addis Ababa University',
                'school_id_number' => 'UGR/1234/15',
                'student_verified' => true,
                'preferred_games' => ['PUBG Mobile', 'EA Sports FC 25', 'Valorant'],
            ]
        );

        // 4. Seed popular esports games
        $games = [
            ['name' => 'EA Sports FC 25', 'slug' => 'fc-25', 'publisher' => 'EA Sports', 'category' => 'sports'],
            ['name' => 'PUBG Mobile', 'slug' => 'pubg-mobile', 'publisher' => 'Krafton / Tencent', 'category' => 'battle_royale'],
            ['name' => 'Valorant', 'slug' => 'valorant', 'publisher' => 'Riot Games', 'category' => 'fps'],
            ['name' => 'Mobile Legends: Bang Bang', 'slug' => 'mlbb', 'publisher' => 'Moonton', 'category' => 'moba'],
            ['name' => 'Free Fire', 'slug' => 'free-fire', 'publisher' => 'Garena', 'category' => 'battle_royale'],
            ['name' => 'Tekken 8', 'slug' => 'tekken-8', 'publisher' => 'Bandai Namco', 'category' => 'fighting'],
            ['name' => 'Rocket League', 'slug' => 'rocket-league', 'publisher' => 'Psyonix', 'category' => 'sports'],
        ];

        foreach ($games as $gameData) {
            Game::firstOrCreate(
                ['slug' => $gameData['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $gameData['name'],
                    'publisher' => $gameData['publisher'],
                    'category' => $gameData['category'],
                    'is_active' => true,
                ]
            );
        }

        $this->call(EducationSeeder::class);
        $this->call(DemoCompetitionSeeder::class);
    }
}
