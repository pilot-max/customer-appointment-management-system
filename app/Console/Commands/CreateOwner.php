<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-owner')]
#[Description('Create the business owner account.')]
class CreateOwner extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Prevent multiple owner accounts from being created
        if (User::query()->exists()) {
            $this->error('A user already exists. No owner account was created.');

            return self::FAILURE;
        }

        // Gather information
        $name = text(
            label: 'Owner name',
            required: true,
            validate: ['name' => 'required|string|max:255'],
        );

        $email = text(
            label: 'Owner email',
            required: true,
            validate: ['email' => 'required|email|max:255|unique:users,email'],
        );

        $password = password(
            label: 'Owner password',
            required: true,
            validate: ['password' => 'required|string|min:12'],
        );

        password(
            label: 'Confirm password',
            required: true,
            validate: fn (string $value) => $value === $password
                ? null
                : 'The passwords do not match.',
        );

        // Create the user
        try {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        } catch (QueryException $exception) {
            report($exception);

            $this->error(
                'Could not create the owner account. Check the application logs for details.'
            );

            return self::FAILURE;
        }

        $this->info('Owner account created successfully.');

        return self::SUCCESS;
    }
}
