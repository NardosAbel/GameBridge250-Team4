<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('gamer_tag')->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('university')->nullable();
            $table->string('school_id_number')->nullable();
            $table->boolean('student_verified')->default(false);
            $table->json('platform_handles')->nullable(); // discord, steam, riot, etc.
            $table->json('preferred_games')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index('university');
            $table->index('student_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
