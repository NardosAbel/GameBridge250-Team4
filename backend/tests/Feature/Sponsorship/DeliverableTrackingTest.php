<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Sponsorship\Enums\DeliverableStatusEnum;
use App\Domain\Sponsorship\Enums\SponsorshipStatusEnum;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->sponsor = User::factory()->create([
        'name' => 'Brand Executive',
        'email' => 'executive@brand.et',
    ]);
    $this->sponsor->assignRole(RoleEnum::SPONSOR->value);

    $this->organizer = User::factory()->create([
        'name' => 'Tournament Director',
        'email' => 'director@campusesports.et',
    ]);
    $this->organizer->assignRole(RoleEnum::ORGANIZER->value);

    $this->brand = BrandProfile::create([
        'user_id' => $this->sponsor->id,
        'company_name' => 'Safaricom Ethiopia',
        'industry' => 'Telecom',
        'contact_email' => 'partnerships@safaricom.et',
        'verified' => true,
    ]);

    $this->package = SponsorshipPackage::create([
        'creator_id' => $this->organizer->id,
        'title' => 'Championship Stage Branding',
        'target_type' => 'tournament',
        'tier' => 'gold',
        'price' => 75000.00,
        'currency' => 'ETB',
        'duration_days' => 30,
        'is_published' => true,
    ]);

    $this->sponsorship = Sponsorship::create([
        'sponsor_id' => $this->sponsor->id,
        'brand_profile_id' => $this->brand->id,
        'package_id' => $this->package->id,
        'applicant_user_id' => $this->sponsor->id,
        'package_name' => $this->package->title,
        'target_type' => 'tournament',
        'amount' => 75000.00,
        'currency' => 'ETB',
        'status' => SponsorshipStatusEnum::ACTIVE,
    ]);

    $this->deliverable = SponsorshipDeliverable::create([
        'sponsorship_id' => $this->sponsorship->id,
        'title' => 'Livestream Lower-Third Overlay',
        'deliverable_type' => 'stream_overlay',
        'status' => DeliverableStatusEnum::PENDING,
    ]);
});

test('deliverable fulfiller can submit evidence and url proof', function () {
    $response = $this->actingAs($this->sponsor, 'sanctum')
        ->postJson("/api/v1/sponsorships/deliverables/{$this->deliverable->id}/evidence", [
            'evidence_url' => 'https://twitch.tv/videos/123456789?t=01h24m10s',
            'evidence_notes' => 'Displayed across semifinal match 2 and grand finals stream',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'submitted',
                'evidence_url' => 'https://twitch.tv/videos/123456789?t=01h24m10s',
            ],
        ]);

    $this->assertDatabaseHas('sponsorship_deliverables', [
        'id' => $this->deliverable->id,
        'status' => DeliverableStatusEnum::SUBMITTED->value,
    ]);
});

test('reviewer can verify deliverable proof', function () {
    $this->deliverable->update([
        'status' => DeliverableStatusEnum::SUBMITTED,
        'evidence_url' => 'https://campusesports.et/proof/finals_overlay.png',
    ]);

    $response = $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/sponsorships/deliverables/{$this->deliverable->id}/verify", [
            'verified' => true,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'verified',
            ],
        ]);

    $this->assertDatabaseHas('sponsorship_deliverables', [
        'id' => $this->deliverable->id,
        'status' => DeliverableStatusEnum::VERIFIED->value,
        'verified_by_id' => $this->organizer->id,
    ]);
});
