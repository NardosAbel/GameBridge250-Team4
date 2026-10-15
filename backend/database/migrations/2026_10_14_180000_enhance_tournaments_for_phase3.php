<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (! Schema::hasColumn('tournaments', 'platform')) {
                $table->string('platform', 50)->default('Cross-platform');
            }
            if (! Schema::hasColumn('tournaments', 'region')) {
                $table->string('region', 100)->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'visibility')) {
                $table->string('visibility', 20)->default('public');
            }
            if (! Schema::hasColumn('tournaments', 'banner_url')) {
                $table->string('banner_url', 255)->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'logo_url')) {
                $table->string('logo_url', 255)->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'theme_color')) {
                $table->string('theme_color', 30)->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'stream_url')) {
                $table->string('stream_url', 255)->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'eligibility_rules')) {
                $table->json('eligibility_rules')->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'points_distribution')) {
                $table->json('points_distribution')->nullable();
            }
            if (! Schema::hasColumn('tournaments', 'settings')) {
                $table->json('settings')->nullable();
            }
        });

        Schema::table('tournament_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('tournament_registrations', 'roster_snapshot')) {
                $table->json('roster_snapshot')->nullable();
            }
            if (! Schema::hasColumn('tournament_registrations', 'qualification_score')) {
                $table->integer('qualification_score')->nullable();
            }
            if (! Schema::hasColumn('tournament_registrations', 'invitation_code')) {
                $table->string('invitation_code', 100)->nullable();
            }
            if (! Schema::hasColumn('tournament_registrations', 'anti_fraud_flags')) {
                $table->json('anti_fraud_flags')->nullable();
            }
            if (! Schema::hasColumn('tournament_registrations', 'placement')) {
                $table->unsignedInteger('placement')->nullable();
            }
            if (! Schema::hasColumn('tournament_registrations', 'points_awarded')) {
                $table->integer('points_awarded')->default(0);
            }
        });

        Schema::table('brackets', function (Blueprint $table) {
            if (! Schema::hasColumn('brackets', 'status')) {
                $table->string('status', 30)->default('pending');
            }
            if (! Schema::hasColumn('brackets', 'settings')) {
                $table->json('settings')->nullable();
            }
        });

        Schema::table('bracket_nodes', function (Blueprint $table) {
            if (! Schema::hasColumn('bracket_nodes', 'group_number')) {
                $table->unsignedSmallInteger('group_number')->nullable();
            }
            if (! Schema::hasColumn('bracket_nodes', 'is_bye')) {
                $table->boolean('is_bye')->default(false);
            }
        });

        Schema::table('matches', function (Blueprint $table) {
            if (! Schema::hasColumn('matches', 'match_format')) {
                $table->string('match_format', 10)->default('bo1');
            }
            if (! Schema::hasColumn('matches', 'round')) {
                $table->unsignedSmallInteger('round')->default(1);
            }
            if (! Schema::hasColumn('matches', 'match_number')) {
                $table->unsignedSmallInteger('match_number')->default(1);
            }
            if (! Schema::hasColumn('matches', 'participant1_checked_in')) {
                $table->boolean('participant1_checked_in')->default(false);
            }
            if (! Schema::hasColumn('matches', 'participant2_checked_in')) {
                $table->boolean('participant2_checked_in')->default(false);
            }
            if (! Schema::hasColumn('matches', 'participant1_ready')) {
                $table->boolean('participant1_ready')->default(false);
            }
            if (! Schema::hasColumn('matches', 'participant2_ready')) {
                $table->boolean('participant2_ready')->default(false);
            }
            if (! Schema::hasColumn('matches', 'room_id')) {
                $table->string('room_id', 100)->nullable();
            }
            if (! Schema::hasColumn('matches', 'room_password')) {
                $table->string('room_password', 100)->nullable();
            }
            if (! Schema::hasColumn('matches', 'room_server')) {
                $table->string('room_server', 100)->nullable();
            }
            if (! Schema::hasColumn('matches', 'room_instructions')) {
                $table->text('room_instructions')->nullable();
            }
            if (! Schema::hasColumn('matches', 'referee_id')) {
                $table->foreignId('referee_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('matches', 'is_bye')) {
                $table->boolean('is_bye')->default(false);
            }
        });

        Schema::table('match_results', function (Blueprint $table) {
            if (! Schema::hasColumn('match_results', 'game_metadata')) {
                $table->json('game_metadata')->nullable();
            }
        });

        Schema::table('disputes', function (Blueprint $table) {
            if (! Schema::hasColumn('disputes', 'evidence_urls')) {
                $table->json('evidence_urls')->nullable();
            }
            if (! Schema::hasColumn('disputes', 'action_taken')) {
                $table->string('action_taken', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn(['evidence_urls', 'action_taken']);
        });

        Schema::table('match_results', function (Blueprint $table) {
            $table->dropColumn('game_metadata');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['referee_id']);
            $table->dropColumn([
                'match_format',
                'round',
                'match_number',
                'participant1_checked_in',
                'participant2_checked_in',
                'participant1_ready',
                'participant2_ready',
                'room_id',
                'room_password',
                'room_server',
                'room_instructions',
                'referee_id',
                'is_bye',
            ]);
        });

        Schema::table('bracket_nodes', function (Blueprint $table) {
            $table->dropColumn(['group_number', 'is_bye']);
        });

        Schema::table('brackets', function (Blueprint $table) {
            $table->dropColumn(['status', 'settings']);
        });

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'roster_snapshot',
                'qualification_score',
                'invitation_code',
                'anti_fraud_flags',
                'placement',
                'points_awarded',
            ]);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'platform',
                'region',
                'visibility',
                'banner_url',
                'logo_url',
                'theme_color',
                'stream_url',
                'eligibility_rules',
                'points_distribution',
                'settings',
            ]);
        });
    }
};
