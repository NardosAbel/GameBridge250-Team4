<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('tag', 10);
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->string('university')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('university');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
