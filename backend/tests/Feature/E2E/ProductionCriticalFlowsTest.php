<?php

use App\Domain\Identity\Actions\SendOtpAction;
use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Profiles\Models\Game;
use App\Domain\Teams\Models\Team;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Ticketing\Services\QrTokenService;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Models\VenueStaff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organizer = User::factory()->create([
        'name' => 'National Esports Organizer',
        'email' => 'organizer@campusesports.et',
    ]);
    $this->organizer->assignRole(RoleEnum::ORGANIZER->value);

    $this->player1 = User::factory()->create([
        'name' => 'Kirubel Tadesse',
        'email' => 'kirubel@aau.edu.et',
        'phone' => '+251911000111',
    ]);
    $this->player1->assignRole(RoleEnum::PLAYER->value);

    $this->player2 = User::factory()->create([
        'name' => 'Yonas Bekele',
        'email' => 'yonas@aau.edu.et',
        'phone' => '+251911000222',
    ]);
    $this->player2->assignRole(RoleEnum::PLAYER->value);

    $this->scanner = User::factory()->create([
        'name' => 'Gate Scanner Staff',
        'email' => 'gate@venue.et',
    ]);
    $this->scanner->assignRole(RoleEnum::ORGANIZER->value);

    $this->game = Game::firstOrCreate(
        ['slug' => 'fc25'],
        ['name' => 'EA Sports FC 25', 'category' => 'Sports', 'is_active' => true]
    );
});

test('e2e: student registration and OTP verification flow', function () {
    // 1. Student registers
    $regResponse = $this->postJson('/api/v1/auth/register', [
        'name' => 'Almaz Ayana',
        'email' => 'almaz@aau.edu.et',
        'phone' => '+251911999888',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
        'gamer_tag' => 'AlmazGamer',
        'university' => 'Addis Ababa University',
    ]);

    $regResponse->assertStatus(201)
        ->assertJson(['success' => true]);

    $user = User::where('email', 'almaz@aau.edu.et')->first();
    expect($user)->not->toBeNull();

    // 2. Generate OTP
    $sendAction = app(SendOtpAction::class);
    $otpResult = $sendAction->execute('almaz@aau.edu.et', 'email_verification', $user);

    // 3. Verify OTP
    $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
        'recipient' => 'almaz@aau.edu.et',
        'code' => $otpResult['code'],
        'type' => 'email_verification',
    ]);

    $verifyResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

test('e2e: team creation, member invitation, and roster management flow', function () {
    // 1. Captain creates team
    $createResponse = $this->actingAs($this->player1, 'sanctum')
        ->postJson('/api/v1/teams', [
            'name' => 'AAU Lions Squad',
            'tag' => 'AAUL',
            'university' => 'Addis Ababa University',
            'recruitment_status' => 'open',
        ]);

    $createResponse->assertStatus(201);
    $teamId = $createResponse->json('data.id');

    // 2. Captain invites teammate
    $inviteResponse = $this->actingAs($this->player1, 'sanctum')
        ->postJson("/api/v1/teams/{$teamId}/invite", [
            'user_id' => $this->player2->id,
            'role' => 'player',
        ]);

    $inviteResponse->assertStatus(201);
    $invitationId = $inviteResponse->json('data.id');

    // 3. Teammate accepts invitation
    $respondResponse = $this->actingAs($this->player2, 'sanctum')
        ->postJson("/api/v1/team-invitations/{$invitationId}/respond", [
            'response' => 'accepted',
        ]);

    $respondResponse->assertStatus(200);

    $team = Team::find($teamId);
    expect($team->members()->where('user_id', $this->player2->id)->exists())->toBeTrue();
});

test('e2e: tournament registration and check-in ready flow', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Addis Ababa Inter-Varsity Cup',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 16,
        'status' => 'registration_open',
    ]);

    // 1. Player registers
    $regResponse = $this->actingAs($this->player1, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register");

    $regResponse->assertStatus(201);

    $this->assertDatabaseHas('tournament_registrations', [
        'tournament_id' => $tournament->id,
        'user_id' => $this->player1->id,
    ]);

    // 2. Tournament enters check-in window
    $tournament->update(['status' => 'check_in']);

    // 3. Player confirms check-in
    $checkInResponse = $this->actingAs($this->player1, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/check-in");

    $checkInResponse->assertStatus(200);

    $reg = TournamentRegistration::where('tournament_id', $tournament->id)
        ->where('user_id', $this->player1->id)
        ->first();
    expect($reg->checked_in)->toBeTrue();
});

test('e2e: match result submission, opponent confirmation, and winner determination flow', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Varsity Showdown Live',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 8,
        'status' => 'live',
    ]);

    $match = TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'participant1_id' => $this->player1->id,
        'participant2_id' => $this->player2->id,
        'participant1_type' => 'user',
        'participant2_type' => 'user',
        'status' => 'ready',
    ]);

    // 1. Participant 1 submits result (2-1 victory)
    $submitResponse = $this->actingAs($this->player1, 'sanctum')
        ->postJson("/api/v1/matches/{$match->id}/result", [
            'participant1_score' => 2,
            'participant2_score' => 1,
            'evidence_url' => 'https://campusesports.et/proof/game_scoreboard.jpg',
            'notes' => 'GGs! Match finished in overtime.',
        ]);

    $submitResponse->assertStatus(200);

    // 2. Participant 2 confirms result
    $confirmResponse = $this->actingAs($this->player2, 'sanctum')
        ->postJson("/api/v1/matches/{$match->id}/confirm");

    $confirmResponse->assertStatus(200);

    $match->refresh();
    expect($match->status->value ?? $match->status)->toBe('completed');
    expect((int) $match->winner_id)->toBe($this->player1->id);
});

test('e2e: ticket purchase, reconciliation, and signed QR generation flow', function () {
    $tier = TicketType::create([
        'name' => 'Grand Finals VIP Student Pass',
        'price' => 150.00,
        'currency' => 'ETB',
        'quantity_total' => 200,
        'category' => 'student',
        'is_active' => true,
    ]);

    // 1. Checkout ticket order
    $checkoutResponse = $this->actingAs($this->player1, 'sanctum')
        ->postJson('/api/v1/tickets/checkout', [
            'items' => [
                ['ticket_type_id' => $tier->id, 'quantity' => 1],
            ],
            'payment_method' => 'telebirr',
        ]);

    $checkoutResponse->assertStatus(201);
    $orderId = $checkoutResponse->json('data.id');

    $order = TicketOrder::find($orderId);
    expect($order)->not->toBeNull();

    // 2. Simulated payment reconciliation (Telebirr webhook or callback)
    $order->update(['status' => 'paid']);

    // 3. Generate signed QR ticket
    $qrService = app(QrTokenService::class);
    $ticket = Ticket::create([
        'ticket_order_id' => $order->id,
        'ticket_type_id' => $tier->id,
        'user_id' => $this->player1->id,
        'status' => 'active',
    ]);
    $payload = $qrService->generatePayload($ticket);

    expect($payload)->toHaveKeys(['ref', 'sig']);
    expect($payload['ref'])->toBe($ticket->uuid);
});

test('e2e: gate scanner validation and duplicate scan rejection flow', function () {
    $tier = TicketType::create([
        'name' => 'General Admission Pass',
        'price' => 50.00,
        'currency' => 'ETB',
        'quantity_total' => 500,
        'category' => 'general_admission',
        'is_active' => true,
    ]);

    $order = TicketOrder::create([
        'user_id' => $this->player1->id,
        'total_amount' => 50.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    $venue = Venue::create([
        'owner_id' => $this->organizer->id,
        'name' => 'Gate Venue',
        'slug' => 'gate-venue',
        'city' => 'Addis Ababa',
        'address' => 'Bole',
        'capacity' => 200,
        'is_verified' => true,
    ]);
    VenueStaff::create([
        'venue_id' => $venue->id,
        'user_id' => $this->scanner->id,
        'role' => 'scanner',
        'is_active' => true,
    ]);
    $event = Event::create([
        'venue_id' => $venue->id,
        'organizer_id' => $this->organizer->id,
        'title' => 'Gate Event',
        'slug' => 'gate-event',
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(4),
        'capacity' => 200,
        'status' => 'published',
    ]);

    $ticket = Ticket::create([
        'ticket_order_id' => $order->id,
        'ticket_type_id' => $tier->id,
        'event_id' => $event->id,
        'venue_id' => $venue->id,
        'user_id' => $this->player1->id,
        'status' => 'active',
    ]);

    $qrService = app(QrTokenService::class);
    $payload = $qrService->generatePayload($ticket);

    // 1. Initial valid scan by gate staff assigned to this venue
    $scan1 = $this->actingAs($this->scanner, 'sanctum')
        ->postJson('/api/v1/tickets/scan', [
            'qr_payload' => $payload,
            'event_id' => $event->id,
            'venue_id' => $venue->id,
        ]);

    $scan1->assertStatus(200)
        ->assertJson([
            'success' => true,
            'status' => 'valid',
        ]);

    $ticket->refresh();
    expect($ticket->status->value ?? $ticket->status)->toBe('checked_in');

    // 2. Duplicate scan attempt must be rejected
    $scan2 = $this->actingAs($this->scanner, 'sanctum')
        ->postJson('/api/v1/tickets/scan', [
            'qr_payload' => $payload,
            'event_id' => $event->id,
            'venue_id' => $venue->id,
        ]);

    $scan2->assertStatus(409)
        ->assertJson([
            'success' => false,
            'status' => 'duplicate',
        ]);
});
