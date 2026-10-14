<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brackets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->string('name')->default('Main Bracket');
            $table->string('stage', 30)->default('playoffs'); // group_stage, upper_bracket, lower_bracket, playoffs
            $table->string('type', 30)->default('single_elimination');
            $table->timestamps();
        });

        Schema::create('bracket_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bracket_id')->constrained('brackets')->cascadeOnDelete();
            $table->unsignedSmallInteger('round')->default(1);
            $table->unsignedSmallInteger('node_order')->default(1);
            $table->unsignedBigInteger('next_node_id')->nullable();
            $table->unsignedBigInteger('loser_next_node_id')->nullable();
            $table->timestamps();

            $table->index(['bracket_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bracket_nodes');
        Schema::dropIfExists('brackets');
    }
};
