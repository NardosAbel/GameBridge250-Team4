<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexSafely('posts', ['user_id', 'created_at'], 'idx_posts_user_created');
        $this->addIndexSafely('tournaments', ['status', 'is_published', 'created_at'], 'idx_tournaments_status_published');
        $this->addIndexSafely('tournaments', ['game_id', 'status'], 'idx_tournaments_game_status');
        $this->addIndexSafely('matches', ['tournament_id', 'status'], 'idx_matches_tournament_status');
        $this->addIndexSafely('tickets', ['ticket_order_id', 'status'], 'idx_tickets_order_status');
        $this->addIndexSafely('tickets', ['event_id', 'status'], 'idx_tickets_event_status');
        $this->addIndexSafely('tickets', ['tournament_id', 'status'], 'idx_tickets_tournament_status');
        $this->addIndexSafely('course_enrollments', ['user_id', 'status'], 'idx_enrollments_user_status');
        $this->addIndexSafely('sponsorships', ['status', 'target_type'], 'idx_sponsorships_status_target');
        $this->addIndexSafely('sponsorships', ['sponsor_id', 'status'], 'idx_sponsorships_sponsor_status');
    }

    public function down(): void
    {
        $this->dropIndexSafely('posts', 'idx_posts_user_created');
        $this->dropIndexSafely('tournaments', 'idx_tournaments_status_published');
        $this->dropIndexSafely('tournaments', 'idx_tournaments_game_status');
        $this->dropIndexSafely('matches', 'idx_matches_tournament_status');
        $this->dropIndexSafely('tickets', 'idx_tickets_order_status');
        $this->dropIndexSafely('tickets', 'idx_tickets_event_status');
        $this->dropIndexSafely('tickets', 'idx_tickets_tournament_status');
        $this->dropIndexSafely('course_enrollments', 'idx_enrollments_user_status');
        $this->dropIndexSafely('sponsorships', 'idx_sponsorships_status_target');
        $this->dropIndexSafely('sponsorships', 'idx_sponsorships_sponsor_status');
    }

    private function addIndexSafely(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->index($columns, $name);
            });
        } catch (Throwable $e) {
            // Index might already exist on rerun
        }
    }

    private function dropIndexSafely(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropIndex($name);
            });
        } catch (Throwable $e) {
            // Ignore if already dropped
        }
    }
};
