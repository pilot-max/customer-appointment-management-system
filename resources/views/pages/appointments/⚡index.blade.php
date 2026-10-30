<?php

use App\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Appointments')]
    class extends Component {
    use WithPagination;

    public string $date = '';
    public string $status = '';
    public bool $upcoming = true;

    public function updated(string $property): void
    {
        if (in_array($property, ['date', 'status', 'upcoming'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function appointments(): LengthAwarePaginator
    {
        $this->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:scheduled,cancelled,completed'],
        ]);

        return Appointment::query()->with('customer')
            ->when($this->date !== '', fn($query) => $query->whereDate('starts_at', $this->date))
            ->when($this->date === '' && $this->upcoming, fn($query) => $query->where(function ($query) {
                $now = now()->format('Y-m-d H:i:s');
                $query->where('starts_at', '>=', $now)
                    ->orWhere(fn($query) => $query->where('status', AppointmentStatus::Scheduled->value)->where('ends_at', '<', $now));
            }))
            ->when($this->status !== '', fn($query) => $query->where('status', $this->status))
            ->orderBy('starts_at')->orderBy('id')->paginate(15);
    }
};
?>

<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Appointments</flux:heading>
            <flux:subheading>Manage the company schedule. All times are entered and displayed unchanged.
            </flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('appointments.create')" wire:navigate>Book appointment</flux:button>
    </div>
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <flux:input wire:model.live="date" label="Date" type="date" />
        <flux:select wire:model.live="status" label="Status">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach (AppointmentStatus::cases() as $option)
                <flux:select.option value="{{ $option->value }}">{{ ucfirst($option->value) }}</flux:select.option>
            @endforeach
        </flux:select>
        <div class="flex items-end pb-3">
            <flux:checkbox wire:model.live="upcoming" label="Upcoming and overdue (when no date is selected)" />
        </div>
    </div>
    <div class="space-y-3">
        @forelse ($this->appointments as $appointment)
            <a href="{{ route('appointments.show', $appointment) }}" wire:navigate
                wire:key="appointment-{{ $appointment->id }}"
                class="block rounded-lg border border-zinc-200 p-4 focus-visible:outline-2 dark:border-zinc-700">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <flux:heading>{{ $appointment->customer?->name ?? 'Removed customer' }} ·
                        {{ $appointment->service_name }}
                    </flux:heading>
                    <div class="flex flex-wrap gap-2">
                        <flux:badge color="{{ $appointment->status->badgeColor() ?? 'zinc' }}">
                            {{ ucfirst($appointment->status->value) }}
                        </flux:badge>
                        @if ($appointment->isOverdue())
                            <flux:badge color="amber">Overdue</flux:badge>
                        @endif
                    </div>
                </div>
                <flux:text>{{ $appointment->starts_at->format('M j, Y H:i') }} –
                    {{ $appointment->ends_at->format('M j, Y H:i') }}
                </flux:text>
            </a>
        @empty
            <flux:text>No appointments match these filters.</flux:text>
        @endforelse
    </div>
    <div class="mt-6">{{ $this->appointments->links() }}</div>
</div>
