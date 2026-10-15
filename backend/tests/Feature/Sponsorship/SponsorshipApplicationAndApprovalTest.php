<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Sponsorship\Enums\SponsorshipStatusEnum;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->sponsor = User::factory()->create([
        'name' => 'Ethio Telecom Brand Rep',
        'email' => 'telecom@brand.et',
    ]);
    $this->sponsor->assignRole(RoleEnum::SPONSOR->value);

    $this->organizer = User::factory()->create([
        'name' => 'AAU League Organizer',
        'email' => 'organizer@aau.et',
    ]);
    $this->organizer->assignRole(RoleEnum::ORGANIZER->value);

    $this->student = User::factory()->create([
        'name' => 'General Player',
        'email' => 'player@aau.et',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    $this->admin = User::factory()->create([
        'name' => 'Platform Super Admin',
        'email' => 'admin@campusesports.et',
    ]);
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
});

test('sponsor can create a verified brand profile', function () {
    $response = $this->actingAs($this->sponsor, 'sanctum')
        ->postJson('/api/v1/sponsors/brands', [
            'company_name' => 'Ethio Telecom Telebirr Gaming',
            'tagline' => 'Powering Digital Gaming across Ethiopia',
            'industry' => 'Telecommunications',
            'contact_email' => 'partnerships@ethiotelecom.et',
            'website_url' => 'https://www.ethiotelecom.et',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'company_name' => 'Ethio Telecom Telebirr Gaming',
                'industry' => 'Telecommunications',
            ],
        ]);

    $this->assertDatabaseHas('brand_profiles', [
        'user_id' => $this->sponsor->id,
        'company_name' => 'Ethio Telecom Telebirr Gaming',
    ]);
});

test('organizer can create a sponsorship package with deliverables', function () {
    $response = $this->actingAs($this->organizer, 'sanctum')
        ->postJson('/api/v1/sponsors/packages', [
            'title' => 'AAU Inter-Campus Championship Title Sponsorship',
            'target_type' => 'tournament',
            'tier' => 'title',
            'price' => 150000.00,
            'currency' => 'ETB',
            'benefits' => ['Main Stage Banner', 'Live Broadcast Overlays', 'Prize Pool Naming'],
            'deliverables' => [
                ['title' => 'Broadcast Overlay Placement', 'deliverable_type' => 'stream_overlay'],
                ['title' => 'Grand Finals Banner Display', 'deliverable_type' => 'physical_banner'],
            ],
            'duration_days' => 45,
            'audience_estimate' => 25000,
            'is_published' => true,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'title' => 'AAU Inter-Campus Championship Title Sponsorship',
                'price' => '150000.00',
            ],
        ]);

    $this->assertDatabaseHas('sponsorship_packages', [
        'creator_id' => $this->organizer->id,
        'title' => 'AAU Inter-Campus Championship Title Sponsorship',
    ]);
});

test('sponsor can apply for a package and target owner can approve it', function () {
    // 1. Create Brand Profile
    $brand = BrandProfile::create([
        'user_id' => $this->sponsor->id,
        'company_name' => 'Chapa Pay',
        'industry' => 'Fintech',
        'contact_email' => 'partner@chapa.co',
        'verified' => true,
    ]);

    // 2. Create Package
    $package = SponsorshipPackage::create([
        'creator_id' => $this->organizer->id,
        'title' => 'National University League Presenting Partner',
        'target_type' => 'tournament',
        'tier' => 'presenting',
        'price' => 85000.00,
        'currency' => 'ETB',
        'benefits' => ['VIP Lounge Branding'],
        'deliverables' => [
            ['title' => 'Stage LED Backdrop', 'deliverable_type' => 'stage_banner'],
        ],
        'duration_days' => 30,
        'is_published' => true,
    ]);

    // 3. Sponsor applies
    $applyResponse = $this->actingAs($this->sponsor, 'sanctum')
        ->postJson('/api/v1/sponsorships/apply', [
            'brand_profile_id' => $brand->id,
            'package_id' => $package->id,
        ]);

    $applyResponse->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'applied',
                'package_name' => 'National University League Presenting Partner',
            ],
        ]);

    $sponsorshipId = $applyResponse->json('data.id');
    $this->assertDatabaseHas('sponsorships', [
        'id' => $sponsorshipId,
        'status' => SponsorshipStatusEnum::APPLIED->value,
    ]);

    // 4. Organizer approves application
    $sponsorship = Sponsorship::find($sponsorshipId);
    $reviewResponse = $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/sponsorships/{$sponsorship->id}/review", [
            'approve' => true,
            'contract_terms' => 'Approved under terms of Addis Ababa Esports Federation agreement.',
        ]);

    $reviewResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'active',
            ],
        ]);

    $this->assertDatabaseHas('sponsorships', [
        'id' => $sponsorshipId,
        'status' => SponsorshipStatusEnum::ACTIVE->value,
        'reviewed_by_id' => $this->organizer->id,
    ]);
});
