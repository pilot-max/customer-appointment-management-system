<?php

use App\Models\Customer;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Customers')]
    class extends Component {
    #[Computed]
    public function customers(): Collection
    {
        return Customer::query()
            ->orderBy('name')
            ->get();
    }

    public function delete(int $id): void
    {
        $customer = Customer::query()->findOrFail($id);

        $customer->delete();

        unset($this->customers);

        Flux::toast(
            text: 'Customer deleted successfully.',
            variant: 'success',
        );
    }
};
?>

<div>
    <flux:heading size="xl">Customers</flux:heading>

    <flux:subheading class="mb-6">
        Manage your customers and their contact details.
    </flux:subheading>

    <div class="mb-6">
        <flux:button variant="primary" :href="route('customers.create')" wire:navigate>
            Add customer
        </flux:button>
    </div>

    <div class="space-y-3">
        @forelse ($this->customers as $customer)
            <div wire:key="customer-{{ $customer->id }}"
                class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <a href="{{ route('customers.show', $customer) }}" wire:navigate
                    class="min-w-0 flex-1 rounded focus-visible:outline-2 focus-visible:outline-offset-4">
                    <flux:heading>{{ $customer->name }}</flux:heading>
                    <flux:text>{{ $customer->phone }}</flux:text>
                    <flux:text class="break-all">{{ $customer->email }}</flux:text>
                </a>

                <div class="flex shrink-0 flex-wrap justify-end gap-2">

                    <flux:button :href="route('customers.edit', $customer)" wire:navigate>
                        Edit
                    </flux:button>

                    <flux:button variant="danger" wire:click="delete({{ $customer->id }})"
                        wire:confirm="Delete this customer?">
                        Delete
                    </flux:button>
                </div>
            </div>
        @empty
            <flux:text>No customers yet.</flux:text>
        @endforelse
    </div>
</div>