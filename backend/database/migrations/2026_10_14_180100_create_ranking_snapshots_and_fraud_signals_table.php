<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->string('type', 20)->default('global'); // global, seasonal, university
            $table->json('rankings_data'); // Top ranking array with user/team, points, rating, rank
            $table->timestamps();

            $table->index(['game_id', 'snapshot_date']);
        });

        Schema::create('tournament_fraud_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('tournament_registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signal_type', 50); // duplicate_ip, duplicate_student_id, multi_team_roster, device_fingerprint_match, banned_player_smurf
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->json('metadata')->nullable(); // matching entity IDs, detected values
            $table->boolean('resolved')->default(false);
            $table->timestamps();

            $table->index(['tournament_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_fraud_signals');
        Schema::dropIfExists('ranking_snapshots');
    }
};
