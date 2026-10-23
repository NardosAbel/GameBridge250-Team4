<?php

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCompetitionSeeder;

test('demo competitions seed once and keep the draft private to the list rules', function () {
    $this->seed(DatabaseSeeder::class);

    $open = Tournament::query()->where('slug', 'aau-pubg-mobile-open')->first();
    expect($open)->not->toBeNull()
        ->and($open->status)->toBe('registration_open')
        ->and($open->visibility)->toBe('public')
        ->and($open->settings['demo'] ?? false)->toBeTrue();

    $draft = Tournament::query()->where('slug', 'draft-campus-invitational')->first();
    expect($draft)->not->toBeNull()
        ->and($draft->status)->toBe('draft');

    $entries = TournamentRegistration::query()->where('tournament_id', $open->id)->count();
    expect($entries)->toBeGreaterThan(0);

    $this->seed(DemoCompetitionSeeder::class);

    expect(Tournament::query()->where('slug', 'aau-pubg-mobile-open')->count())->toBe(1)
        ->and(Tournament::query()->where('slug', 'draft-campus-invitational')->count())->toBe(1)
        ->and(TournamentRegistration::query()->where('tournament_id', $open->id)->count())->toBe($entries);

    $this->getJson('/api/v1/tournaments')
        ->assertOk()
        ->assertJsonFragment(['title' => 'AAU PUBG Mobile Open'])
        ->assertJsonMissing(['title' => 'Draft Campus Invitational']);
});
