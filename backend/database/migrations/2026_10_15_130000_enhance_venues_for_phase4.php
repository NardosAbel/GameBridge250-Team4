<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance venues table
        Schema::table('venues', function (Blueprint $table) {
            if (! Schema::hasColumn('venues', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('venues', 'map_link')) {
                $table->string('map_link', 500)->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('venues', 'opening_hours')) {
                $table->json('opening_hours')->nullable()->after('facilities');
            }
            if (! Schema::hasColumn('venues', 'contact_details')) {
                $table->json('contact_details')->nullable()->after('opening_hours');
            }
            if (! Schema::hasColumn('venues', 'rules')) {
                $table->text('rules')->nullable()->after('contact_details');
            }
            if (! Schema::hasColumn('venues', 'pricing_details')) {
                $table->json('pricing_details')->nullable()->after('hourly_rate');
            }
            if (! Schema::hasColumn('venues', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('verified');
            }
        });

        // 2. Enhance events table
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'tournament_id')) {
                $table->foreignId('tournament_id')->nullable()->after('venue_id')->constrained('tournaments')->nullOnDelete();
            }
            if (! Schema::hasColumn('events', 'banner_url')) {
                $table->string('banner_url')->nullable()->after('title');
            }
            if (! Schema::hasColumn('events', 'event_type')) {
                $table->string('event_type', 50)->default('tournament')->after('description'); // tournament, lan_party, community_scrim, viewing_party
            }
            if (! Schema::hasColumn('events', 'recurring_pattern')) {
                $table->json('recurring_pattern')->nullable()->after('end_time');
            }
            if (! Schema::hasColumn('events', 'capacity')) {
                $table->unsignedInteger('capacity')->default(100)->after('recurring_pattern');
            }
        });

        // 3. Create venue_staff table for scanner and manager accounts
        if (! Schema::hasTable('venue_staff')) {
            Schema::create('venue_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 30)->default('scanner'); // manager, scanner
                $table->timestamps();

                $table->unique(['venue_id', 'user_id', 'role']);
                $table->index(['venue_id', 'role']);
            });
        }

        // 4. Create venue_reviews table
        if (! Schema::hasTable('venue_reviews')) {
            Schema::create('venue_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating'); // 1 - 5 stars
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->unique(['venue_id', 'user_id']);
                $table->index('venue_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_reviews');
        Schema::dropIfExists('venue_staff');

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['tournament_id']);
            $table->dropColumn(['tournament_id', 'banner_url', 'event_type', 'recurring_pattern', 'capacity']);
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['description', 'map_link', 'opening_hours', 'contact_details', 'rules', 'pricing_details', 'is_featured']);
        });
    }
};
