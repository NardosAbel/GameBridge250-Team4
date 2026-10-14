<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('type', 30)->default('text'); // text, clip, livestream, announcement, poll
            $table->string('visibility', 20)->default('public'); // public, community, team
            $table->timestamps();
            $table->softDeletes();

            $table->index(['community_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('media_url');
            $table->string('media_type', 30)->default('image'); // image, video, clip
            $table->string('thumbnail_url')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media');
        Schema::dropIfExists('posts');
    }
};
