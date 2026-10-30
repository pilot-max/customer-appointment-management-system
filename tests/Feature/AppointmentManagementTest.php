<?php

use App\Actions\Appointments\CreateAppointment;
use App\Actions\Appointments\SaveAppointment;
use App\Models\User;
use Database\Factories\AppointmentFactory;
use Database\Factories\CustomerFactory;
use Database\Factories\ServiceFactory;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('only scheduled appointments whose end time has passed are overdue', function (string $status, string $end, bool $overdue) {
    $this->travelTo(now()->setDate(2030, 1, 10)->setTime(11, 0, 0));
    $booking = AppointmentFactory::new()->create(['status' => $status, 'ends_at' => $end]);

    expect($booking->isOverdue())->toBe($overdue);
})->with([
    'finished scheduled' => ['scheduled', '2030-01-10 10:59:59', true],
    'exact end' => ['scheduled', '2030-01-10 11:00:00', false],
    'in progress' => ['scheduled', '2030-01-10 11:30:00', false],
    'completed' => ['completed', '2030-01-10 10:59:59', false],
    'cancelled' => ['cancelled', '2030-01-10 10:59:59', false],
]);

test('appointment details show overdue until the owner completes the appointment', function () {
    $this->travelTo(now()->setDate(2030, 1, 10)->setTime(12, 0, 0));
    $this->actingAs(User::factory()->create());
    $booking = AppointmentFactory::new()->create();

    Livewire::test('pages::appointments.show', ['appointment' => $booking])
        ->assertSee('Overdue')->assertSee('Scheduled')
        ->call('complete')->assertDontSee('Overdue')->assertSee('Completed');
});

test('the default list includes overdue bookings while retaining date and status filters', function () {
    $this->travelTo(now()->setDate(2030, 1, 10)->setTime(12, 0, 0));
    $this->actingAs(User::factory()->create());
    AppointmentFactory::new()->create(['service_name' => 'Overdue booking']);
    AppointmentFactory::new()->create(['service_name' => 'Past completed', 'status' => 'completed']);
    AppointmentFactory::new()->create(['service_name' => 'Past cancelled', 'status' => 'cancelled']);
    AppointmentFactory::new()->create(['service_name' => 'Future booking', 'starts_at' => '2030-01-11 10:00:00', 'ends_at' => '2030-01-11 11:00:00']);

    Livewire::test('pages::appointments.index')
        ->assertSeeInOrder(['Overdue booking', 'Future booking'])->assertSee('Overdue')
        ->assertDontSee('Past completed')->assertDontSee('Past cancelled')
        ->set('status', 'completed')->assertDontSee('Overdue booking')
        ->set('date', '2030-01-10')->assertSee('Past completed')->assertDontSee('Overdue')
        ->set('status', 'scheduled')->set('date', '2030-01-11')
        ->assertSee('Future booking')->assertDontSee('Overdue booking')->assertDontSee('Overdue');
});

test('guests cannot access appointment pages', function (string $routeName) {
    $appointment = AppointmentFactory::new()->create();

    $this->get(route($routeName, ['appointment' => $appointment]))->assertRedirect(route('login'));
})->with(['appointments.index', 'appointments.create', 'appointments.edit', 'appointments.show']);

test('the owner can open each appointment page', function (string $routeName) {
    $this->actingAs(User::factory()->create());
    $appointment = AppointmentFactory::new()->create();

    $this->get(route($routeName, ['appointment' => $appointment]))->assertOk();
})->with(['appointments.index', 'appointments.create', 'appointments.edit', 'appointments.show']);

test('booking stores entered clock time unchanged and snapshots the service', function () {
    $customer = CustomerFactory::new()->create();
    $service = ServiceFactory::new()->create(['name' => 'Haircut', 'price' => '30.50', 'duration_minutes' => 45]);

    $booking = app(CreateAppointment::class)->handle($customer->id, $service->id, '2030-03-10T02:30');

    $this->assertDatabaseHas('appointments', ['id' => $booking->id,
        'starts_at' => '2030-03-10 02:30:00', 'ends_at' => '2030-03-10 03:15:00',
        'service_name' => 'Haircut', 'service_price' => '30.50', 'service_duration_minutes' => 45,
        'status' => 'scheduled',
    ]);
});

test('overlapping non-cancelled bookings are rejected', function (string $start, string $status) {
    AppointmentFactory::new()->create(['status' => $status]);
    $customer = CustomerFactory::new()->create();
    $service = ServiceFactory::new()->create(['duration_minutes' => 60]);

    try {
        app(CreateAppointment::class)->handle($customer->id, $service->id, $start);
        $this->fail('Expected an overlap validation error.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['starts_at'])->toBe(['This time overlaps an existing appointment.']);
    }
    $this->assertDatabaseCount('appointments', 1);
})->with(['2030-01-10T09:30', '2030-01-10T10:00', '2030-01-10T10:30'])->with(['scheduled', 'completed']);

test('adjacent appointments are accepted', function (string $start) {
    AppointmentFactory::new()->create();
    $customer = CustomerFactory::new()->create();
    $service = ServiceFactory::new()->create(['duration_minutes' => 60]);

    $booking = app(CreateAppointment::class)->handle($customer->id, $service->id, $start);

    $this->assertModelExists($booking);
    $this->assertDatabaseCount('appointments', 2);
})->with(['2030-01-10T09:00', '2030-01-10T11:00']);

test('cancelled appointments release their slots', function () {
    AppointmentFactory::new()->create(['status' => 'cancelled']);
    $customer = CustomerFactory::new()->create();
    $service = ServiceFactory::new()->create(['duration_minutes' => 60]);

    $booking = app(CreateAppointment::class)->handle($customer->id, $service->id, '2030-01-10T10:00');

    $this->assertModelExists($booking);
});

test('invalid appointment dates are rejected without saving', function (string $start) {
    $customer = CustomerFactory::new()->create();
    $service = ServiceFactory::new()->create();

    expect(fn () => app(CreateAppointment::class)->handle($customer->id, $service->id, $start))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('appointments', 0);
})->with(['', 'tomorrow', '2030-02-30T10:00', '2030-01-10T25:00']);

test('removed customers and services cannot be used for new bookings', function (string $removed) {
    $customer = CustomerFactory::new()->create();
    $service = ServiceFactory::new()->create();
    ($removed === 'customer' ? $customer : $service)->delete();

    expect(fn () => app(CreateAppointment::class)->handle($customer->id, $service->id, '2030-01-10T10:00'))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('appointments', 0);
})->with(['customer', 'service']);

test('the booking form validates required fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::appointments.create')->call('save')
        ->assertHasErrors(['customer_id' => 'required', 'service_id' => 'required', 'starts_at' => 'required']);
    $this->assertDatabaseCount('appointments', 0);
});

test('the booking form saves and redirects to details with a toast', function () {
    $this->actingAs(User::factory()->create());
    $customer = CustomerFactory::new()->create();
    $service = ServiceFactory::new()->create();

    $component = Livewire::test('pages::appointments.create')->set('customer_id', (string) $customer->id)
        ->set('service_id', (string) $service->id)->set('starts_at', '2030-01-10T10:00')->call('save');

    $component->assertHasNoErrors()->assertDispatched('toast-show');
    $this->assertDatabaseHas('appointments', ['customer_id' => $customer->id, 'service_id' => $service->id]);
});

test('rescheduling excludes itself and preserves original details after catalogue edits', function () {
    $booking = AppointmentFactory::new()->create();
    $service = $booking->service;
    $service->name = 'Changed service';
    $service->price = '100.00';
    $service->duration_minutes = 120;
    $service->save();

    $updated = app(SaveAppointment::class)->handle($booking->customer_id, $booking->service_id, '2030-01-10T10:00', $booking);

    expect($updated->service_name)->toBe('Haircut');
    expect($updated->service_price)->toBe('30.00');
    expect($updated->ends_at->format('H:i'))->toBe('11:00');
    $this->assertDatabaseCount('appointments', 1);
});

test('rescheduling into another booking is rejected', function () {
    $this->actingAs(User::factory()->create());
    $booking = AppointmentFactory::new()->create();
    AppointmentFactory::new()->create(['starts_at' => '2030-01-10 12:00:00', 'ends_at' => '2030-01-10 13:00:00']);

    Livewire::test('pages::appointments.edit', ['appointment' => $booking])->set('starts_at', '2030-01-10T12:00')->call('save')->assertHasErrors('starts_at');
    $this->assertDatabaseHas('appointments', ['id' => $booking->id, 'starts_at' => '2030-01-10 10:00:00']);
});

test('removed customer and service details remain available on existing appointments', function () {
    $this->actingAs(User::factory()->create());
    $booking = AppointmentFactory::new()->create();
    $name = $booking->customer->name;
    $booking->customer->delete();
    $booking->service->delete();

    $this->get(route('appointments.show', $booking))->assertOk()->assertSee($name)->assertSee('Haircut')->assertSee('30.00');
    $updated = app(SaveAppointment::class)->handle($booking->customer_id, $booking->service_id, '2030-01-10T12:00', $booking);
    expect($updated->service_name)->toBe('Haircut');
});

test('scheduled appointments can be cancelled or completed from details', function (string $action, string $status) {
    $this->actingAs(User::factory()->create());
    $booking = AppointmentFactory::new()->create();

    Livewire::test('pages::appointments.show', ['appointment' => $booking])->call($action)->assertDispatched('toast-show');

    $this->assertDatabaseHas('appointments', ['id' => $booking->id, 'status' => $status]);
})->with([['cancel', 'cancelled'], ['complete', 'completed']]);

test('terminal appointments cannot be edited or reactivated', function (string $status) {
    $booking = AppointmentFactory::new()->create(['status' => $status]);

    expect(fn () => app(SaveAppointment::class)->handle($booking->customer_id, $booking->service_id, '2030-01-10T12:00', $booking))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('appointments', ['id' => $booking->id, 'status' => $status]);
})->with(['cancelled', 'completed']);

test('appointment filters select date and status in chronological order', function () {
    $this->actingAs(User::factory()->create());
    AppointmentFactory::new()->create(['service_name' => 'Later booking', 'starts_at' => '2030-01-10 12:00:00', 'ends_at' => '2030-01-10 13:00:00']);
    AppointmentFactory::new()->create(['service_name' => 'Earlier booking']);
    AppointmentFactory::new()->create(['service_name' => 'Cancelled booking', 'status' => 'cancelled']);
    AppointmentFactory::new()->create(['service_name' => 'Other date', 'starts_at' => '2030-01-11 10:00:00']);

    Livewire::test('pages::appointments.index')->set('date', '2030-01-10')->set('status', 'scheduled')
        ->assertSeeInOrder(['Earlier booking', 'Later booking'])->assertDontSee('Cancelled booking')->assertDontSee('Other date');
});
