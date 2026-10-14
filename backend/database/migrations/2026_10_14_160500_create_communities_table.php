<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type', 30)->default('interest'); // school, game, city, venue, interest
            $table->string('visibility', 20)->default('public'); // public, private, approval
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('avatar_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
