<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, cancelled
            $table->boolean('checked_in')->default(false);
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedInteger('seed')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id']);
            $table->unique(['tournament_id', 'team_id']);
            $table->index(['tournament_id', 'status']);
        });

        Schema::create('tournament_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('referee'); // admin, referee, caster, observer
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_staff');
        Schema::dropIfExists('tournament_registrations');
    }
};
