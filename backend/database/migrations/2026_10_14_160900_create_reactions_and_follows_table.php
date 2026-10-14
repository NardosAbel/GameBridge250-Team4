<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reactable');
            $table->string('type', 20)->default('like'); // like, fire, trophy, gg
            $table->timestamps();

            $table->unique(['user_id', 'reactable_type', 'reactable_id', 'type'], 'user_reactable_unique');
        });

        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('followable');
            $table->timestamps();

            $table->unique(['follower_id', 'followable_type', 'followable_id'], 'follower_followable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
        Schema::dropIfExists('reactions');
    }
};
