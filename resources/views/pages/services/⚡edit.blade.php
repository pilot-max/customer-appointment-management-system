<?php

use App\Models\Service;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component {
    public function render(): View
    {
        return $this->view()->title('Edit ' . $this->service->name);
    }

    public Service $service;

    public string $name = '';

    public string $price = '';

    public string $duration_minutes = '';

    public function mount(Service $service): void
    {
        $this->service = $service;

        $this->name = $service->name;
        $this->price = $service->price;
        $this->duration_minutes = (string) $service->duration_minutes;
    }


    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $this->service->name = $validated['name'];
        $this->service->price = $validated['price'];
        $this->service->duration_minutes = $validated['duration_minutes'];
        $this->service->save();

        Flux::toast(
            text: 'Service updated successfully.',
            variant: 'success',
        );

        $this->redirectRoute('services.index', navigate: true);
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl">Edit service</flux:heading>

    <flux:subheading class="mb-6">
        Update the service details used when booking appointments.
    </flux:subheading>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="name" label="Service name" required />

        <flux:input wire:model="price" label="Price" type="number" min="0" step="0.01" required />

        <flux:input wire:model="duration_minutes" label="Duration (minutes)" type="number" min="1" step="1" required />

        <flux:button type="submit" variant="primary">
            Save service
        </flux:button>

        <flux:button :href="route('services.index')" wire:navigate>
            Cancel
        </flux:button>
    </form>
</div>