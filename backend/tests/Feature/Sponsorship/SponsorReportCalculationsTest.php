<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Sponsorship\Enums\DeliverableStatusEnum;
use App\Domain\Sponsorship\Enums\SponsorshipStatusEnum;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Sponsorship\Models\SponsorshipDeliverable;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->sponsor = User::factory()->create([
        'name' => 'Commercial Partner',
        'email' => 'partner@company.com',
    ]);
    $this->sponsor->assignRole(RoleEnum::SPONSOR->value);

    $this->brand = BrandProfile::create([
        'user_id' => $this->sponsor->id,
        'company_name' => 'Ride Gaming',
        'industry' => 'Mobility',
        'contact_email' => 'ads@ride.et',
        'verified' => true,
    ]);

    $this->sponsorship = Sponsorship::create([
        'sponsor_id' => $this->sponsor->id,
        'brand_profile_id' => $this->brand->id,
        'applicant_user_id' => $this->sponsor->id,
        'package_name' => 'Campus Cup Gold Partner',
        'target_type' => 'tournament',
        'amount' => 50000.00,
        'currency' => 'ETB',
        'status' => SponsorshipStatusEnum::ACTIVE,
        'total_impressions' => 100000,
        'total_clicks' => 2500,
    ]);

    SponsorshipDeliverable::create([
        'sponsorship_id' => $this->sponsorship->id,
        'title' => 'Broadcast Placement 1',
        'status' => DeliverableStatusEnum::VERIFIED,
    ]);

    SponsorshipDeliverable::create([
        'sponsorship_id' => $this->sponsorship->id,
        'title' => 'Broadcast Placement 2',
        'status' => DeliverableStatusEnum::PENDING,
    ]);
});

test('calculates accurate CTR, CPM, CPC, and fulfillment rate for sponsor report', function () {
    $response = $this->actingAs($this->sponsor, 'sanctum')
        ->getJson("/api/v1/sponsorships/{$this->sponsorship->id}/report");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'total_impressions' => 100000,
                'total_clicks' => 2500,
                'click_through_rate' => 2.5, // 2500 / 100000 * 100 = 2.5%
                'cpm' => 500.0,              // 50000 / 100000 * 1000 = 500 ETB
                'cpc' => 20.0,               // 50000 / 2500 = 20 ETB
                'deliverables' => [
                    'total' => 2,
                    'verified' => 1,
                    'fulfillment_rate' => 50,
                ],
            ],
        ]);
});

test('allows exporting sponsorship performance report as CSV stream', function () {
    $response = $this->actingAs($this->sponsor, 'sanctum')
        ->get("/api/v1/sponsorships/{$this->sponsorship->id}/report/export");

    $response->assertStatus(200);
    $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    $this->assertStringContainsString('Total Impressions', $response->getContent());
    $this->assertStringContainsString('100000', $response->getContent());
    $this->assertStringContainsString('Click Through Rate (%)', $response->getContent());
});
