<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create brand_profiles table
        if (! Schema::hasTable('brand_profiles')) {
            Schema::create('brand_profiles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('company_name');
                $table->string('slug')->unique();
                $table->string('tagline')->nullable();
                $table->text('description')->nullable();
                $table->string('industry', 60)->default('gaming_gear'); // gaming_gear, telco, fintech, energy_drink, apparel, education, tech
                $table->string('logo_url')->nullable();
                $table->string('banner_url')->nullable();
                $table->string('website_url')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 30)->nullable();
                $table->json('social_links')->nullable();
                $table->boolean('verified')->default(false);
                $table->timestamps();

                $table->index(['industry', 'verified']);
            });
        }

        // 2. Create sponsorship_packages table
        if (! Schema::hasTable('sponsorship_packages')) {
            Schema::create('sponsorship_packages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('target_type', 40)->default('tournament'); // tournament, team, player, venue, creator, league, awareness_campaign, platform
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('tier', 30)->default('gold'); // title, presenting, gold, silver, bronze, custom
                $table->decimal('price', 12, 2);
                $table->string('currency', 3)->default('ETB');
                $table->json('benefits')->nullable();
                $table->json('deliverables')->nullable();
                $table->unsignedInteger('duration_days')->default(30);
                $table->unsignedInteger('audience_estimate')->default(5000);
                $table->boolean('is_published')->default(true);
                $table->timestamps();

                $table->index(['target_type', 'target_id', 'is_published']);
            });
        }

        // 3. Enhance sponsorships table
        Schema::table('sponsorships', function (Blueprint $table) {
            if (! Schema::hasColumn('sponsorships', 'brand_profile_id')) {
                $table->foreignId('brand_profile_id')->nullable()->after('sponsor_id')->constrained('brand_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('sponsorships', 'package_id')) {
                $table->foreignId('package_id')->nullable()->after('brand_profile_id')->constrained('sponsorship_packages')->nullOnDelete();
            }
            if (! Schema::hasColumn('sponsorships', 'applicant_user_id')) {
                $table->foreignId('applicant_user_id')->nullable()->after('package_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('sponsorships', 'reviewed_by_id')) {
                $table->foreignId('reviewed_by_id')->nullable()->after('applicant_user_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('sponsorships', 'contract_number')) {
                $table->string('contract_number', 64)->nullable()->unique()->after('reviewed_by_id');
            }
            if (! Schema::hasColumn('sponsorships', 'contract_terms')) {
                $table->text('contract_terms')->nullable()->after('contract_number');
            }
            if (! Schema::hasColumn('sponsorships', 'start_date')) {
                $table->timestamp('start_date')->nullable()->after('contract_terms');
            }
            if (! Schema::hasColumn('sponsorships', 'end_date')) {
                $table->timestamp('end_date')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('sponsorships', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('sponsorships', 'total_impressions')) {
                $table->unsignedInteger('total_impressions')->default(0)->after('rejection_reason');
            }
            if (! Schema::hasColumn('sponsorships', 'total_clicks')) {
                $table->unsignedInteger('total_clicks')->default(0)->after('total_impressions');
            }
            if (! Schema::hasColumn('sponsorships', 'roi_metrics')) {
                $table->json('roi_metrics')->nullable()->after('total_clicks');
            }
        });

        // 4. Create sponsorship_deliverables table
        if (! Schema::hasTable('sponsorship_deliverables')) {
            Schema::create('sponsorship_deliverables', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('sponsorship_id')->constrained('sponsorships')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('deliverable_type', 40)->default('stream_overlay'); // stream_overlay, banner_ad, jersey_logo, social_mention, venue_standee, prize_pool_bonus, custom
                $table->timestamp('due_date')->nullable();
                $table->string('evidence_url', 500)->nullable();
                $table->text('evidence_notes')->nullable();
                $table->string('status', 30)->default('pending'); // pending, submitted, verified, rejected
                $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->text('rejection_notes')->nullable();
                $table->timestamps();

                $table->index(['sponsorship_id', 'status']);
            });
        }

        // 5. Create prize_pool_contributions table
        if (! Schema::hasTable('prize_pool_contributions')) {
            Schema::create('prize_pool_contributions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
                $table->foreignId('sponsor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('brand_profile_id')->nullable()->constrained('brand_profiles')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('ETB');
                $table->string('donor_name')->nullable();
                $table->boolean('is_anonymous')->default(false);
                $table->text('message')->nullable();
                $table->string('status', 30)->default('paid'); // pledged, paid, refunded
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['tournament_id', 'status']);
            });
        }

        // 6. Create brand_placements table
        if (! Schema::hasTable('brand_placements')) {
            Schema::create('brand_placements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();
                $table->foreignId('sponsorship_id')->nullable()->constrained('sponsorships')->nullOnDelete();
                $table->string('title');
                $table->string('placement_type', 40)->default('feed_banner'); // feed_banner, tournament_overlay, sidebar_ad, sponsored_post, video_preroll
                $table->string('media_url', 500);
                $table->string('destination_url', 500);
                $table->unsignedInteger('impressions_count')->default(0);
                $table->unsignedInteger('clicks_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->timestamps();

                $table->index(['placement_type', 'is_active']);
            });
        }

        // 7. Create platform_feature_flags table
        if (! Schema::hasTable('platform_feature_flags')) {
            Schema::create('platform_feature_flags', function (Blueprint $table) {
                $table->id();
                $table->string('key', 60)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->string('audience', 40)->default('all'); // all, beta_testers, organizers_only, students_only
                $table->json('metadata')->nullable();
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_feature_flags');
        Schema::dropIfExists('brand_placements');
        Schema::dropIfExists('prize_pool_contributions');
        Schema::dropIfExists('sponsorship_deliverables');
        Schema::dropIfExists('sponsorship_packages');
        Schema::dropIfExists('brand_profiles');
    }
};
