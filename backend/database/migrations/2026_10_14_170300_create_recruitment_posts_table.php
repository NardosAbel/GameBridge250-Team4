<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('type', 20)->default('lft'); // lft (looking for team), lfp (looking for players)
            $table->string('title');
            $table->text('description');
            $table->string('rank_tier')->nullable();
            $table->string('role')->nullable();
            $table->string('location')->nullable();
            $table->string('university')->nullable();
            $table->string('age_group', 30)->nullable(); // u18, university_student, open
            $table->string('availability')->nullable(); // weekdays, weekends, evenings, flexible
            $table->string('platform', 30)->nullable(); // pc, mobile, playstation, xbox
            $table->string('status', 20)->default('open'); // open, closed
            $table->timestamps();

            $table->index(['type', 'game_id', 'status']);
            $table->index(['university', 'status']);
            $table->index(['location', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_posts');
    }
};
