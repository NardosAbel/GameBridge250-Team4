<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('handle')->nullable()->unique()->after('gamer_tag');
            $table->string('banner_url')->nullable()->after('cover_url');
            $table->string('location')->nullable()->after('university');
            $table->json('roles')->nullable()->after('platform_handles'); // in-game roles e.g. entry, sniper, support
            $table->json('platforms')->nullable()->after('roles'); // pc, mobile, console
            $table->json('availability')->nullable()->after('platforms'); // weekends, evenings, fulltime
            $table->json('privacy_settings')->nullable()->after('is_public'); // stats_visibility, allow_dms, allow_invites
            $table->json('achievements')->nullable()->after('privacy_settings');
            $table->json('badges')->nullable()->after('achievements');

            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->dropColumn([
                'handle',
                'banner_url',
                'location',
                'roles',
                'platforms',
                'availability',
                'privacy_settings',
                'achievements',
                'badges',
            ]);
        });
    }
};
