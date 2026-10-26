<?php

use App\Models\User;
use Database\Factories\ServiceFactory;

test('guests cannot view service details', function () {
    $service = ServiceFactory::new()->create();

    $response = $this->get(route('services.show', $service));

    $response->assertRedirect(route('login'));
});

test('the owner can view service details and navigation links', function () {
    $user = User::factory()->create();
    $service = ServiceFactory::new()->create([
        'name' => '<b>Haircut</b>',
        'price' => '30.50',
        'duration_minutes' => 45,
    ]);

    $response = $this->actingAs($user)->get(route('services.show', $service));

    $response->assertOk()
        ->assertSee('<b>Haircut</b>')
        ->assertDontSee('<b>Haircut</b>', false)
        ->assertSee('30.50')
        ->assertSee('45 minutes')
        ->assertSee(route('services.edit', $service))
        ->assertSee(route('services.index'));
});

test('missing services return not found', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('services.show', 999));

    $response->assertNotFound();
});

test('deleted services cannot be viewed from the catalogue', function () {
    $user = User::factory()->create();
    $service = ServiceFactory::new()->trashed()->create();

    $response = $this->actingAs($user)->get(route('services.show', $service));

    $response->assertNotFound();
});

test('the service list links to service details', function () {
    $user = User::factory()->create();
    $service = ServiceFactory::new()->create();

    $response = $this->actingAs($user)->get(route('services.index'));

    $response->assertOk()->assertSee(route('services.show', $service));
});
