<?php

use App\Domain\Tournaments\Support\RankingLedgerBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('tickets', 'issuance_slot')) {
                $table->string('issuance_slot')->nullable()->unique();
            }
        });

        if (! Schema::hasTable('tournament_ranking_awards')) {
            Schema::create('tournament_ranking_awards', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
                $table->foreignId('ranking_id')->constrained('rankings')->cascadeOnDelete();
                $table->unsignedInteger('points');
                $table->timestamps();

                $table->unique(['tournament_id', 'ranking_id']);
            });
        }

        (new RankingLedgerBackfill)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_ranking_awards');

        Schema::table('tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('tickets', 'issuance_slot')) {
                $table->dropUnique(['issuance_slot']);
                $table->dropColumn('issuance_slot');
            }
        });
    }
};
