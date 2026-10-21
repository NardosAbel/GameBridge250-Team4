<?php

use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration does not place the verification code in the redirect url', function () {
    $response = $this->post('/register', [
        'name' => 'Hana Bekele',
        'email' => 'hana@example.com',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'gamer_tag' => 'HanaGG',
    ]);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->not->toContain('devCode');
});
