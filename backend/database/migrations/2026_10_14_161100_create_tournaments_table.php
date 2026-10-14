<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('format', 30)->default('single_elimination'); // single_elimination, double_elimination, round_robin, swiss, group_knockout
            $table->string('match_format', 10)->default('bo1'); // bo1, bo3, bo5
            $table->string('event_type', 20)->default('online'); // online, offline, hybrid
            $table->string('entry_type', 20)->default('free'); // free, paid, invitation, qualification
            $table->decimal('entry_fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('ETB');
            $table->decimal('prize_pool', 12, 2)->nullable();
            $table->unsignedInteger('capacity')->default(16);
            $table->text('rules')->nullable();
            $table->timestamp('registration_start_at')->nullable();
            $table->timestamp('registration_end_at')->nullable();
            $table->timestamp('check_in_start_at')->nullable();
            $table->timestamp('check_in_end_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 30)->default('draft'); // draft, published, registration_open, registration_closed, check_in, live, completed, cancelled, archived
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['game_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
