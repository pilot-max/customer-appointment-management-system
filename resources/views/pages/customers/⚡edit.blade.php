<?php

use App\Models\Customer;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit customer')]
    class extends Component {
    public Customer $customer;
    public string $name = '';
    public string $phone = '';
    public string $email = '';

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;

        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->email = $customer->email;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'regex:/^\+?(?=(?:\D*\d){7,15}\D*$)[0-9 ().-]+$/'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'phone.regex' => 'Enter a phone number with 7 to 15 digits. You may use a leading +, spaces, parentheses, dots, and hyphens.',
        ]);

        $this->customer->name = $validated['name'];
        $this->customer->phone = $validated['phone'];
        $this->customer->email = $validated['email'];
        $this->customer->save();

        Flux::toast(
            text: 'Customer updated successfully.',
            variant: 'success',
        );

        $this->redirectRoute('customers.index', navigate: true);
    }
};
?>

<div class="max-w-xl">
    <flux:heading size="xl">Edit customer</flux:heading>

    <flux:subheading class="mb-6">
        Edit the customer's contact details.
    </flux:subheading>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="name" label="Customer name" autocomplete="name" required />

        <flux:input wire:model="phone" label="Phone number" type="tel" autocomplete="tel" required />

        <flux:input wire:model="email" label="Email address" type="email" autocomplete="email" required />

        <flux:button type="submit" variant="primary">
            Save changes
        </flux:button>

        <flux:button :href="route('customers.index')" wire:navigate>
            Cancel
        </flux:button>
    </form>
</div>
