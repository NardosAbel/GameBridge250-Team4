<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Standard permissions across platform domains
        $permissions = [
            // Tournaments
            'tournaments.view',
            'tournaments.create',
            'tournaments.manage',
            'tournaments.register',
            'matches.submit_result',
            'matches.confirm_result',
            'matches.dispute',
            'matches.referee',

            // Teams & Communities
            'teams.create',
            'teams.manage',
            'communities.create',
            'communities.manage',

            // Ticketing & Scanning
            'tickets.purchase',
            'tickets.scan',
            'venues.manage',

            // Content & Social
            'posts.create',
            'content.moderate',

            // Learning
            'courses.view',
            'courses.manage',

            // Admin & Audit
            'users.manage',
            'roles.manage',
            'audit_logs.view',
            'platform.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create the 9 required platform roles
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');

            match ($roleEnum) {
                RoleEnum::SUPER_ADMIN => $role->syncPermissions(Permission::all()),
                RoleEnum::MODERATOR => $role->syncPermissions([
                    'tournaments.view', 'content.moderate', 'matches.dispute', 'audit_logs.view',
                ]),
                RoleEnum::ORGANIZER => $role->syncPermissions([
                    'tournaments.view', 'tournaments.create', 'tournaments.manage',
                    'matches.submit_result', 'matches.confirm_result', 'matches.referee', 'tickets.scan',
                ]),
                RoleEnum::VENUE_ADMIN => $role->syncPermissions([
                    'venues.manage', 'tickets.scan', 'tournaments.view',
                ]),
                RoleEnum::TEAM_CAPTAIN => $role->syncPermissions([
                    'teams.create', 'teams.manage', 'tournaments.view', 'tournaments.register',
                    'matches.submit_result', 'matches.confirm_result', 'matches.dispute', 'posts.create',
                ]),
                RoleEnum::SCHOOL_ADMIN => $role->syncPermissions([
                    'communities.create', 'communities.manage', 'tournaments.view', 'courses.view',
                ]),
                RoleEnum::SPONSOR => $role->syncPermissions([
                    'tournaments.view',
                ]),
                RoleEnum::CREATOR => $role->syncPermissions([
                    'posts.create', 'tournaments.view',
                ]),
                RoleEnum::PLAYER => $role->syncPermissions([
                    'tournaments.view', 'tournaments.register', 'matches.dispute',
                    'tickets.purchase', 'posts.create', 'courses.view',
                ]),
            };
        }
    }
}
