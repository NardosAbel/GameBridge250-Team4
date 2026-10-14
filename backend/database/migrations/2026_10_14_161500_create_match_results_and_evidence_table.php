<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('submitted_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('confirmed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('participant1_score');
            $table->unsignedSmallInteger('participant2_score');
            $table->string('status', 30)->default('submitted'); // submitted, confirmed, disputed, overridden
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'status']);
        });

        Schema::create('match_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_result_id')->constrained('match_results')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30)->default('screenshot'); // screenshot, clip, match_id, note
            $table->string('url');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_evidence');
        Schema::dropIfExists('match_results');
    }
};
