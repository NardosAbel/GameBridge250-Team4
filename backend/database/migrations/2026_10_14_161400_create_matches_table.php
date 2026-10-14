<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('bracket_node_id')->nullable()->constrained('bracket_nodes')->nullOnDelete();
            $table->unsignedBigInteger('participant1_id')->nullable();
            $table->unsignedBigInteger('participant2_id')->nullable();
            $table->string('participant1_type', 30)->default('user'); // user, team
            $table->string('participant2_type', 30)->default('user');
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->string('status', 30)->default('pending'); // pending, ready, live, completed, disputed, forfeited
            $table->unsignedSmallInteger('participant1_score')->default(0);
            $table->unsignedSmallInteger('participant2_score')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
