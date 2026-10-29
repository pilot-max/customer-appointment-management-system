<?php

use App\Actions\Appointments\SaveAppointment;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit appointment')] class extends Component {
    public string $customer_id = '';
    public string $service_id = '';
    public string $starts_at = '';
    public Appointment $appointment;

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment;
        $this->customer_id = (string) $appointment->customer_id;
        $this->service_id = (string) $appointment->service_id;
        $this->starts_at = $appointment->starts_at->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function customers(): Collection
    {
        return Customer::query()->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $this->appointment->customer_id))->orderBy('name')->get();
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::query()->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $this->appointment->service_id))->orderBy('name')->get();
    }

    public function save(SaveAppointment $action): void
    {
        $this->validate([
            'customer_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);
        $booking = $action->handle((int) $this->customer_id, (int) $this->service_id, $this->starts_at, $this->appointment);
        Flux::toast(text: 'Appointment updated successfully.', variant: 'success');
        $this->redirectRoute('appointments.show', ['appointment' => $booking->id], navigate: true);
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl">Edit appointment</flux:heading>
    <flux:subheading class="mb-6">Choose a customer, service, and appointment time.</flux:subheading>
    <flux:text class="mb-4">Booked service: {{ $appointment->service_name }} · {{ $appointment->service_price }} · {{ $appointment->service_duration_minutes }} minutes. Keeping the same service preserves these details.</flux:text>
    <form wire:submit="save" class="space-y-6">
        <flux:select wire:model="customer_id" label="Customer" placeholder="Choose a customer" required>
            @foreach ($this->customers as $customer)
                <flux:select.option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->trashed() ? ' (removed)' : '' }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="service_id" label="Service" placeholder="Choose a service" required>
            @foreach ($this->services as $service)
                <flux:select.option value="{{ $service->id }}">{{ $service->name }} - {{ $service->duration_minutes }} minutes{{ $service->trashed() ? ' (removed)' : '' }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="starts_at" label="Start date and time" type="datetime-local" required />
        <flux:text>Enter the company's local time. The end time is calculated from the booked service duration.</flux:text>
        <div class="flex flex-wrap gap-3">
            <flux:button type="submit" variant="primary">Save appointment</flux:button>
            <flux:button :href="route('appointments.index')" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
