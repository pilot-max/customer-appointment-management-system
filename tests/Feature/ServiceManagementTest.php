<?php

use App\Models\User;
use Database\Factories\ServiceFactory;
use Livewire\Livewire;

test('the owner can create a service', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test('pages::services.create')
        ->set('name', 'Haircut')
        ->set('price', '30.50')
        ->set('duration_minutes', '45')
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseHas('services', [
        'name' => 'Haircut',
        'price' => '30.50',
        'duration_minutes' => 45,
        'deleted_at' => null,
    ]);
});

test('service creation requires a name, price, and duration', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test('pages::services.create')
        ->call('save');

    $component->assertHasErrors([
        'name' => 'required',
        'price' => 'required',
        'duration_minutes' => 'required',
    ]);

    $this->assertDatabaseCount('services', 0);
});

dataset('invalid service fields', [
    'missing name' => ['name', '', 'required'],
    'long name' => ['name', str_repeat('a', 256), 'max'],
    'missing price' => ['price', '', 'required'],
    'non-numeric price' => ['price', 'free', 'numeric'],
    'negative price' => ['price', '-0.01', 'min'],
    'oversized price' => ['price', '100000000', 'max'],
    'excess price precision' => ['price', '12.345', 'decimal'],
    'missing duration' => ['duration_minutes', '', 'required'],
    'non-numeric duration' => ['duration_minutes', 'hour', 'integer'],
    'fractional duration' => ['duration_minutes', '1.5', 'integer'],
    'zero duration' => ['duration_minutes', '0', 'min'],
    'negative duration' => ['duration_minutes', '-1', 'min'],
    'oversized duration' => ['duration_minutes', '1441', 'max'],
]);

test('invalid service data is not created', function (string $field, string $value, string $rule) {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('pages::services.create')
        ->set('name', 'Haircut')
        ->set('price', '30.50')
        ->set('duration_minutes', '45')
        ->set($field, $value)
        ->call('save');

    $component->assertHasErrors([$field => $rule]);
    $this->assertDatabaseCount('services', 0);
})->with('invalid service fields');

test('valid boundary values can be saved', function (string $price, string $duration) {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('pages::services.create')
        ->set('name', str_repeat('a', 255))
        ->set('price', $price)
        ->set('duration_minutes', $duration)
        ->call('save');

    $component->assertHasNoErrors()->assertRedirect(route('services.index'));
    $this->assertDatabaseHas('services', [
        'name' => str_repeat('a', 255),
        'price' => $price,
        'duration_minutes' => (int) $duration,
    ]);
})->with([
    'free service and minimum duration' => ['0', '1'],
    'maximum price and duration' => ['99999999.99', '1440'],
]);

test('the edit form loads the selected service', function () {
    $this->actingAs(User::factory()->create());
    $service = ServiceFactory::new()->create([
        'name' => 'Haircut', 'price' => '30.50', 'duration_minutes' => 45,
    ]);

    $component = Livewire::test('pages::services.edit', ['service' => $service]);

    $component->assertSet('name', 'Haircut')
        ->assertSet('price', '30.50')
        ->assertSet('duration_minutes', '45');
});

test('the owner can update a service without changing another service', function () {
    $this->actingAs(User::factory()->create());
    $service = ServiceFactory::new()->create();
    $other = ServiceFactory::new()->create([
        'name' => 'Beard trim', 'price' => '15.00', 'duration_minutes' => 15,
    ]);

    $component = Livewire::test('pages::services.edit', ['service' => $service])
        ->set('name', 'Hair colouring')
        ->set('price', '90.00')
        ->set('duration_minutes', '90')
        ->call('save');

    $component->assertHasNoErrors()->assertRedirect(route('services.index'));
    $this->assertDatabaseHas('services', [
        'id' => $service->id, 'name' => 'Hair colouring',
        'price' => '90.00', 'duration_minutes' => 90,
    ]);
    $this->assertDatabaseHas('services', [
        'id' => $other->id, 'name' => 'Beard trim',
        'price' => '15.00', 'duration_minutes' => 15,
    ]);
    $this->assertDatabaseCount('services', 2);
    expect(session('success'))->toBe('Service updated successfully.');
});

test('invalid edits leave the stored service unchanged', function (string $field, string $value, string $rule) {
    $this->actingAs(User::factory()->create());
    $service = ServiceFactory::new()->create([
        'name' => 'Haircut', 'price' => '30.50', 'duration_minutes' => 45,
    ]);

    $component = Livewire::test('pages::services.edit', ['service' => $service])
        ->set($field, $value)
        ->call('save');

    $component->assertHasErrors([$field => $rule]);
    $this->assertDatabaseHas('services', [
        'id' => $service->id, 'name' => 'Haircut',
        'price' => '30.50', 'duration_minutes' => 45,
    ]);
    $this->assertDatabaseCount('services', 1);
})->with('invalid service fields');

test('deleting a service retains its record and removes it from the list', function () {
    $this->actingAs(User::factory()->create());
    $service = ServiceFactory::new()->create(['name' => 'Haircut']);
    $other = ServiceFactory::new()->create(['name' => 'Beard trim']);
    $component = Livewire::test('pages::services.index');
    $component->assertSee('Haircut');

    $component->call('delete', $service->id);

    $component->assertDontSee('Haircut')->assertSee('Beard trim')
        ->assertSee('Service deleted successfully.');
    $this->assertSoftDeleted($service);
    $this->assertNotSoftDeleted($other);
    $this->assertDatabaseCount('services', 2);
});

test('the service list shows its empty state when only deleted services remain', function () {
    $this->actingAs(User::factory()->create());
    ServiceFactory::new()->trashed()->create(['name' => 'Retired service']);

    $component = Livewire::test('pages::services.index');

    $component->assertDontSee('Retired service')
        ->assertSee('No services yet.');
});

test('available services are listed alphabetically', function () {
    $this->actingAs(User::factory()->create());
    ServiceFactory::new()->create(['name' => 'Zebra service']);
    ServiceFactory::new()->create(['name' => 'Alpha service']);

    $response = $this->get(route('services.index'));

    $response->assertOk()->assertSeeInOrder(['Alpha service', 'Zebra service']);
});

test('guests cannot access service management pages', function (string $routeName) {
    $service = ServiceFactory::new()->create();

    $response = $this->get(route($routeName, ['service' => $service]));

    $response->assertRedirect(route('login'));
})->with(['services.index', 'services.create', 'services.edit']);

test('missing or deleted services cannot be edited', function (bool $deleted) {
    $this->actingAs(User::factory()->create());
    $id = $deleted
        ? ServiceFactory::new()->trashed()->create()->id
        : 999;

    $response = $this->get(route('services.edit', $id));

    $response->assertNotFound();
})->with(['missing' => false, 'deleted' => true]);
