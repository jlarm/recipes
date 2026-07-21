<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

beforeEach(function () {
    config()->set('recipes.passcode', 'secret-code');
});

it('shows the passcode form', function () {
    get(route('passcode.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Passcode'));
});

it('rejects an incorrect passcode', function () {
    post(route('passcode.verify'), ['passcode' => 'wrong'])
        ->assertSessionHasErrors('passcode');

    expect(session()->get('passcode_verified'))->toBeNull();
});

it('verifies a correct passcode and marks the session', function () {
    $response = post(route('passcode.verify'), ['passcode' => 'secret-code']);

    $response->assertRedirect(route('recipes.create'));

    expect(session()->get('passcode_verified'))->toBeTrue();
});

it('returns the visitor to their intended destination after verifying', function () {
    withSession(['passcode_intended_url' => route('recipes.create')])
        ->post(route('passcode.verify'), ['passcode' => 'secret-code'])
        ->assertRedirect(route('recipes.create'));
});

it('rejects any passcode when none is configured', function () {
    config()->set('recipes.passcode', null);

    post(route('passcode.verify'), ['passcode' => 'anything'])
        ->assertSessionHasErrors('passcode');
});
