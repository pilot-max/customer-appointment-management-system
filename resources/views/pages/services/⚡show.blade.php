<?php

use App\Models\Service;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component {
    public function render(): View
    {
        return $this->view()->title($this->service->name);
    }

    public Service $service;

    public function mount(Service $service): void
    {
        $this->service = $service;
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl">{{ $service->name }}</flux:heading>
    <flux:subheading class="mb-6">
        Service details used when booking appointments.
    </flux:subheading>

    <dl class="mb-6 space-y-4">
        <div>
            <dt><flux:heading>Price</flux:heading></dt>
            <dd><flux:text>{{ $service->price }}</flux:text></dd>
        </div>
        <div>
            <dt><flux:heading>Duration</flux:heading></dt>
            <dd><flux:text>{{ $service->duration_minutes }} minutes</flux:text></dd>
        </div>
    </dl>

    <div class="flex flex-wrap gap-3">
        <flux:button variant="primary" :href="route('services.edit', $service)" wire:navigate>
            Edit service
        </flux:button>
        <flux:button :href="route('services.index')" wire:navigate>
            Back to services
        </flux:button>
    </div>
</div>
