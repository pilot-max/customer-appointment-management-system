<?php

use App\Models\Service;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Services')]
    class extends Component {
    #[Computed]
    public function services(): Collection
    {
        return Service::query()
            ->orderBy('name')
            ->get();
    }

    public function delete(int $id): void
    {
        $service = Service::query()->findOrFail($id);

        $service->delete();

        unset($this->services);


        Flux::toast(
            text: 'Service deleted successfully.',
            variant: 'success',
        );
    }
};
?>

<div>
    <flux:heading size="xl">Services</flux:heading>

    <flux:subheading class="mb-6">
        Manage the services available for appointments.
    </flux:subheading>

    <div class="mb-6">
        <flux:button variant="primary" :href="route('services.create')" wire:navigate>
            Add service
        </flux:button>
    </div>

    @if (session('success'))
        <flux:text class="mb-4">
            {{ session('success') }}
        </flux:text>
    @endif

    <div class="space-y-3">
        @forelse ($this->services as $service)
            <div wire:key="service-{{ $service->id }}"
                class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <a href="{{ route('services.show', $service) }}" wire:navigate
                    class="min-w-0 flex-1 rounded focus-visible:outline-2 focus-visible:outline-offset-4">
                    <flux:heading class="break-words">{{ $service->name }}</flux:heading>

                    <flux:text>
                        Price: {{ $service->price }}
                        · Duration: {{ $service->duration_minutes }} minutes
                    </flux:text>
                </a>

                <div class="flex shrink-0 flex-wrap justify-end gap-2">
                    <flux:button :href="route('services.edit', $service)" wire:navigate>
                        Edit
                    </flux:button>

                    <flux:button variant="danger" wire:click="delete({{ $service->id }})"
                        wire:confirm="Delete this service? Existing appointments will be retained.">
                        Delete
                    </flux:button>
                </div>
            </div>
        @empty
            <flux:text>
                No services yet. Add your first service to start scheduling appointments.
            </flux:text>
        @endforelse
    </div>
</div>
