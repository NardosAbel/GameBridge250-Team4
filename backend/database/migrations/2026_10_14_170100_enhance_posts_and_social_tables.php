<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->after('team_id')->constrained('games')->nullOnDelete();
            $table->string('location_tag')->nullable()->after('type');
            $table->json('hashtags')->nullable()->after('location_tag');
            $table->json('poll_data')->nullable()->after('hashtags');
            $table->boolean('pinned')->default(false)->after('visibility');
            $table->unsignedInteger('shares_count')->default(0)->after('pinned');
            $table->unsignedInteger('views_count')->default(0)->after('shares_count');

            $table->index('pinned');
            $table->index('location_tag');
        });

        Schema::table('post_media', function (Blueprint $table) {
            $table->string('moderation_status', 30)->default('approved')->after('thumbnail_url'); // approved, pending_review, rejected
            $table->unsignedBigInteger('file_size')->nullable()->after('moderation_status');
            $table->string('mime_type', 100)->nullable()->after('file_size');

            $table->index('moderation_status');
        });

        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'post_id']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('bookmarkable');
            $table->timestamps();

            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id'], 'user_bookmark_unique');
        });

        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'blocked_user_id']);
        });

        Schema::create('user_mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('muted_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'muted_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mutes');
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('shares');

        Schema::table('post_media', function (Blueprint $table) {
            $table->dropIndex(['moderation_status']);
            $table->dropColumn(['moderation_status', 'file_size', 'mime_type']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['pinned']);
            $table->dropIndex(['location_tag']);
            $table->dropForeign(['game_id']);
            $table->dropColumn([
                'game_id',
                'location_tag',
                'hashtags',
                'poll_data',
                'pinned',
                'shares_count',
                'views_count',
            ]);
        });
    }
};
