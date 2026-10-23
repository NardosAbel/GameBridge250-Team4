<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('tournament_id')->nullable()->after('game_id')->constrained('tournaments')->nullOnDelete();
            $table->boolean('sponsored')->default(false)->after('pinned');
            $table->index(['tournament_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['tournament_id', 'created_at']);
            $table->dropConstrainedForeignId('tournament_id');
            $table->dropColumn('sponsored');
        });
    }
};
