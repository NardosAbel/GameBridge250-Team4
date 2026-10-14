<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsorships', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sponsor_id')->constrained('users')->cascadeOnDelete();
            $table->string('package_name');
            $table->string('target_type', 30)->default('platform'); // tournament, event, team, platform
            $table->unsignedBigInteger('target_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('ETB');
            $table->string('status', 30)->default('draft'); // draft, proposed, active, completed, cancelled
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorships');
    }
};
