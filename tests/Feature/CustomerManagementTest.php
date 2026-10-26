<?php

use App\Models\User;
use Database\Factories\CustomerFactory;
use Livewire\Livewire;

dataset('invalid customer fields', [
    'missing name' => ['name', '', 'required'],
    'long name' => ['name', str_repeat('a', 256), 'max'],
    'missing phone' => ['phone', '', 'required'],
    'long phone' => ['phone', str_repeat('1', 256), 'max'],
    'missing email' => ['email', '', 'required'],
    'invalid email' => ['email', 'not-an-email', 'email'],
    'long email' => ['email', str_repeat('a', 245).'@example.com', 'max'],
]);

test('the owner can create a customer and receives a success toast', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('pages::customers.create')
        ->set('name', 'Alex Smith')->set('phone', '+1 (416) 555-0123')
        ->set('email', 'alex@example.com')->call('save');

    $component->assertHasNoErrors()->assertRedirect(route('customers.index'))
        ->assertDispatched('toast-show', fn ($event, $params) => $params['slots']['text'] === 'Customer created successfully.'
            && $params['dataset']['variant'] === 'success');
    $this->assertDatabaseHas('customers', [
        'name' => 'Alex Smith', 'phone' => '+1 (416) 555-0123',
        'email' => 'alex@example.com', 'deleted_at' => null,
    ]);
});

test('invalid customer input is not saved', function (string $field, string $value, string $rule) {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('pages::customers.create')
        ->set('name', 'Alex Smith')->set('phone', '0416 555 0123')
        ->set('email', 'alex@example.com')->set($field, $value)->call('save');

    $component->assertHasErrors([$field => $rule])->assertNotDispatched('toast-show');
    $this->assertDatabaseCount('customers', 0);
})->with('invalid customer fields');

test('customers can share contact information', function () {
    $this->actingAs(User::factory()->create());
    CustomerFactory::new()->create(['email' => 'family@example.com', 'phone' => '04165550123']);

    $component = Livewire::test('pages::customers.create')
        ->set('name', 'Another family member')->set('phone', '04165550123')
        ->set('email', 'family@example.com')->call('save');

    $component->assertHasNoErrors();
    $this->assertDatabaseCount('customers', 2);
});

test('the edit form loads the correct customer', function () {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create([
        'name' => 'Alex Smith', 'phone' => '04165550123', 'email' => 'alex@example.com',
    ]);

    $component = Livewire::test('pages::customers.edit', ['customer' => $customer]);

    $component->assertSet('name', 'Alex Smith')->assertSet('phone', '04165550123')
        ->assertSet('email', 'alex@example.com');
});

test('the owner can update a customer without changing another customer', function () {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create();
    $other = CustomerFactory::new()->create(['name' => 'Unchanged', 'phone' => '0123456789', 'email' => 'other@example.com']);

    $component = Livewire::test('pages::customers.edit', ['customer' => $customer])
        ->set('name', 'Updated customer')->set('phone', '+44 20 7946 0958')
        ->set('email', 'updated@example.com')->call('save');

    $component->assertHasNoErrors()->assertRedirect(route('customers.index'))
        ->assertDispatched('toast-show', fn ($event, $params) => $params['slots']['text'] === 'Customer updated successfully.'
            && $params['dataset']['variant'] === 'success');
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated customer', 'phone' => '+44 20 7946 0958', 'email' => 'updated@example.com']);
    $this->assertDatabaseHas('customers', ['id' => $other->id, 'name' => 'Unchanged', 'phone' => '0123456789', 'email' => 'other@example.com']);
    $this->assertDatabaseCount('customers', 2);
});

test('invalid edits leave customer details unchanged', function (string $field, string $value, string $rule) {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create(['name' => 'Alex Smith', 'phone' => '04165550123', 'email' => 'alex@example.com']);

    $component = Livewire::test('pages::customers.edit', ['customer' => $customer])
        ->set($field, $value)->call('save');

    $component->assertHasErrors([$field => $rule])->assertNotDispatched('toast-show');
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Alex Smith', 'phone' => '04165550123', 'email' => 'alex@example.com']);
})->with('invalid customer fields');

test('deleting a customer retains the record and refreshes the list with a toast', function () {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create(['name' => 'Customer to delete']);
    $other = CustomerFactory::new()->create(['name' => 'Remaining customer']);
    $component = Livewire::test('pages::customers.index');
    $component->assertSee('Customer to delete');

    $component->call('delete', $customer->id);

    $component->assertDontSee('Customer to delete')->assertSee('Remaining customer')
        ->assertDispatched('toast-show', fn ($event, $params) => $params['slots']['text'] === 'Customer deleted successfully.'
            && $params['dataset']['variant'] === 'success');
    $this->assertSoftDeleted($customer);
    $this->assertNotSoftDeleted($other);
    $this->assertDatabaseCount('customers', 2);
});

test('the customer list is alphabetical and links to details and editing', function () {
    $this->actingAs(User::factory()->create());
    CustomerFactory::new()->create(['name' => 'Zoe Smith']);
    $customer = CustomerFactory::new()->create(['name' => 'Alex Smith']);
    CustomerFactory::new()->trashed()->create(['name' => 'Deleted customer']);

    $response = $this->get(route('customers.index'));

    $response->assertOk()->assertSeeInOrder(['Alex Smith', 'Zoe Smith'])
        ->assertDontSee('Deleted customer')->assertSee(route('customers.show', $customer))
        ->assertSee(route('customers.edit', $customer))->assertSee(route('customers.create'));
});

test('the empty customer list displays feedback', function () {
    $this->actingAs(User::factory()->create());
    CustomerFactory::new()->trashed()->create();

    $response = $this->get(route('customers.index'));

    $response->assertOk()->assertSee('No customers yet.');
});

test('customer details are escaped and contain contact details and navigation', function () {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create(['name' => '<b>Alex</b>', 'phone' => '+1 416 555 0123', 'email' => 'alex@example.com']);

    $response = $this->get(route('customers.show', $customer));

    $response->assertOk()->assertSee('<b>Alex</b>')->assertDontSee('<b>Alex</b>', false)
        ->assertSee('+1 416 555 0123')->assertSee('alex@example.com')
        ->assertSee(route('customers.edit', $customer))->assertSee(route('customers.index'));
});

test('guests cannot access customer pages', function (string $routeName) {
    $customer = CustomerFactory::new()->create();

    $response = $this->get(route($routeName, ['customer' => $customer]));

    $response->assertRedirect(route('login'));
})->with(['customers.index', 'customers.create', 'customers.edit', 'customers.show']);

test('the owner can open the customer creation form', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('customers.create'));

    $response->assertOk()->assertSee('Add customer')->assertSee('Save customer');
});

test('missing customers cannot be viewed or edited', function (string $routeName) {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route($routeName, 999));

    $response->assertNotFound();
})->with(['customers.show', 'customers.edit']);

test('deleted customers cannot be viewed or edited', function (string $routeName) {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->trashed()->create();

    $response = $this->get(route($routeName, $customer));

    $response->assertNotFound();
})->with(['customers.show', 'customers.edit']);

test('customer creation rejects alphabetic phone input', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('pages::customers.create')
        ->set('name', 'Alex Smith')->set('phone', 'abc')->set('email', 'alex@example.com')->call('save');

    $component->assertHasErrors('phone');
    $this->assertDatabaseCount('customers', 0);
});

test('customer editing rejects alphabetic phone input', function () {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create(['phone' => '+1 416 555 0123']);

    $component = Livewire::test('pages::customers.edit', ['customer' => $customer])->set('phone', 'abc')->call('save');

    $component->assertHasErrors('phone');
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'phone' => '+1 416 555 0123']);
});

test('customer forms reject malformed phone numbers without saving', function (string $page, string $phone) {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create(['phone' => '+1 416 555 0123']);
    $component = Livewire::test('pages::customers.'.$page, ['customer' => $customer]);

    $component->set('name', 'Alex Smith')->set('email', 'alex@example.com')
        ->set('phone', $phone)->call('save');

    $component->assertHasErrors(['phone' => 'regex'])
        ->assertSee('Enter a phone number with 7 to 15 digits.')
        ->assertNotDispatched('toast-show');
    $this->assertDatabaseCount('customers', 1);
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'phone' => '+1 416 555 0123']);
})->with(['create', 'edit'])->with([
    'too few digits' => '123456',
    'too many digits' => '1234567890123456',
    'formatting without digits' => '(---)',
    'letters mixed with digits' => '416CALLNOW',
    'embedded plus' => '416+5550123',
    'multiple plus signs' => '++14165550123',
    'unsupported symbols' => '416/555/0123',
]);

test('customer forms accept common phone formats and digit boundaries', function (string $page, string $phone) {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create();
    $component = Livewire::test('pages::customers.'.$page, ['customer' => $customer]);

    $component->set('name', 'Alex Smith')->set('email', 'alex@example.com')
        ->set('phone', $phone)->call('save');

    $component->assertHasNoErrors()->assertRedirect(route('customers.index'));
    $this->assertDatabaseHas('customers', ['name' => 'Alex Smith', 'email' => 'alex@example.com', 'phone' => $phone]);
})->with(['create', 'edit'])->with([
    'international' => '+44 20 7946 0958',
    'parentheses' => '+1 (416) 555-0123',
    'leading zero' => '0416 555 0123',
    'dots' => '416.555.0123',
    'minimum digits' => '1234567',
    'maximum digits' => '+123456789012345',
]);
