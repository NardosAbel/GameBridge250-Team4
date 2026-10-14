<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->after('leader_id')->constrained('games')->nullOnDelete();
            $table->string('location')->nullable()->after('university');
            $table->string('recruitment_status', 30)->default('open')->after('is_active'); // open, closed, by_application
            $table->json('achievements')->nullable()->after('recruitment_status');

            $table->index('recruitment_status');
        });

        Schema::create('team_roster_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 30); // joined, left, promoted, demoted, removed, transferred
            $table->string('role', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['team_id', 'created_at']);
        });

        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30)->default('invitation'); // invitation (from team), join_request (from player)
            $table->string('role', 30)->default('player');
            $table->string('status', 30)->default('pending'); // pending, accepted, declined, cancelled
            $table->foreignId('invited_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_roster_history');

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['recruitment_status']);
            $table->dropForeign(['game_id']);
            $table->dropColumn(['game_id', 'location', 'recruitment_status', 'achievements']);
        });
    }
};
