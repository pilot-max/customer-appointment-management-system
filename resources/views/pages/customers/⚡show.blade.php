<?php

use App\Models\Customer;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Customer details')]
    class extends Component {
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl">{{ $customer->name }}</flux:heading>

    <dl class="my-6 space-y-4">
        <div>
            <dt>
                <flux:heading>Phone number</flux:heading>
            </dt>
            <dd>
                <flux:text>{{ $customer->phone }}</flux:text>
            </dd>
        </div>

        <div>
            <dt>
                <flux:heading>Email address</flux:heading>
            </dt>
            <dd>
                <flux:text class="break-all">
                    {{ $customer->email }}
                </flux:text>
            </dd>
        </div>
    </dl>

    <div class="flex flex-wrap gap-3">
        <flux:button variant="primary" :href="route('customers.edit', $customer)" wire:navigate>
            Edit customer
        </flux:button>

        <flux:button :href="route('customers.index')" wire:navigate>
            Back to customers
        </flux:button>
    </div>
</div>