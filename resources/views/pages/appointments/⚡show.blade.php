<?php

use App\Actions\Appointments\ChangeAppointmentStatus;
use App\AppointmentStatus;
use App\Models\Appointment;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appointment details')] class extends Component {
    public Appointment $appointment;

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment->load('customer');
    }

    public function cancel(ChangeAppointmentStatus $action): void
    {
        $this->appointment = $action->handle($this->appointment, AppointmentStatus::Cancelled)->load('customer');
        Flux::toast(text: 'Appointment cancelled successfully.', variant: 'success');
    }

    public function complete(ChangeAppointmentStatus $action): void
    {
        $this->appointment = $action->handle($this->appointment, AppointmentStatus::Completed)->load('customer');
        Flux::toast(text: 'Appointment completed successfully.', variant: 'success');
    }
};
?>

<div class="max-w-2xl">
    <flux:heading size="xl">Appointment details</flux:heading>
    <div class="my-4"><flux:badge>{{ ucfirst($appointment->status->value) }}</flux:badge></div>
    <flux:error name="status" />
    <dl class="my-6 grid gap-5 sm:grid-cols-2">
        <div><dt><flux:heading>Customer</flux:heading></dt><dd><flux:text>{{ $appointment->customer?->name ?? 'Removed customer' }}</flux:text></dd></div>
        <div><dt><flux:heading>Booked service</flux:heading></dt><dd><flux:text>{{ $appointment->service_name }}</flux:text></dd></div>
        <div><dt><flux:heading>Start</flux:heading></dt><dd><flux:text>{{ $appointment->starts_at->format('M j, Y H:i') }}</flux:text></dd></div>
        <div><dt><flux:heading>End</flux:heading></dt><dd><flux:text>{{ $appointment->ends_at->format('M j, Y H:i') }}</flux:text></dd></div>
        <div><dt><flux:heading>Booked price</flux:heading></dt><dd><flux:text>{{ $appointment->service_price }}</flux:text></dd></div>
        <div><dt><flux:heading>Booked duration</flux:heading></dt><dd><flux:text>{{ $appointment->service_duration_minutes }} minutes</flux:text></dd></div>
    </dl>
    <div class="flex flex-wrap gap-3">
        @if ($appointment->status === AppointmentStatus::Scheduled)
            <flux:button variant="primary" :href="route('appointments.edit', $appointment)" wire:navigate>Edit / reschedule</flux:button>
            <flux:button wire:click="complete" wire:confirm="Mark this appointment as completed?">Complete</flux:button>
            <flux:button variant="danger" wire:click="cancel" wire:confirm="Cancel this appointment and release its time slot?">Cancel appointment</flux:button>
        @endif
        <flux:button :href="route('appointments.index')" wire:navigate>Back to appointments</flux:button>
    </div>
</div>
