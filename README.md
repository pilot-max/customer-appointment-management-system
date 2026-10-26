# Customer & Appointment Management System

An owner-only internal application for a single-location service business, built as a simulated US$300 fixed-price freelance contract with a 14-day scope.

Built with Laravel 13, Livewire 4, Flux UI, and Tailwind CSS. GitHub Projects is the task backlog.

## Current progress

- Owner login/logout and an interactive owner-account creation command.
- Service management: list, create, view, edit, validate, and soft-delete services.
- Service navigation, page titles, and automated service feature tests.
- Customer management and appointment scheduling remain planned work. In particular, preservation of booked service details must still be implemented and tested with appointments.

## Requirements

- PHP 8.4, matching the project's CI environment, with the extensions required by Composer and SQLite support (`pdo_sqlite` and `sqlite3`).
- Composer 2.
- Node.js 22.18 or newer in the Node 22 release line, matching the installed frontend tooling; npm.
- Git.

The default local database is SQLite. MySQL or another supported database can be configured separately, but the steps below use SQLite.

## First-time local installation

Run these commands from a terminal:

```bash
git clone https://github.com/pilot-max/customer-appointment-management-system.git
cd customer-appointment-management-system
composer install
npm ci
cp .env.example .env
touch database/database.sqlite
```

The `cp` and `touch` commands assume a macOS/Linux shell. Copy `.env.example` to `.env` and create the empty SQLite file manually if using another shell. For an existing checkout, retain its existing `.env` rather than copying over it.

Edit `.env` before continuing:

```dotenv
APP_NAME="Customer & Appointment Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

With the default SQLite configuration, leaving `DB_DATABASE` unset uses `database/database.sqlite`. If you set `DB_DATABASE`, supply the absolute path to that file. No database username or password is required for SQLite. The database-backed session, cache, and queue drivers use tables created by the supplied migrations.

Then run:

```bash
php artisan key:generate
php artisan migrate
npm run build
php artisan app:create-owner
```

Generate the application key only for a new installation; retain the key on an existing installation. Keep `.env` and credentials out of Git. `.env.example` contains placeholders, not real credentials.

Do not use `migrate --seed` or `db:seed` for this setup: the current default seeder creates a sample user, which would prevent the owner-creation command from running.

### Owner account

`php artisan app:create-owner` prompts for a name, email address, password, and password confirmation. Password input is masked and must contain at least 12 characters. The password is stored as a hash.

There are no shared default login credentials. Use the email and password you enter to log in. The command refuses to create an account if **any user already exists**; it does not overwrite accounts or reset passwords.

Public registration and email verification are disabled. Password resets, optional two-factor authentication, and passkeys are currently enabled in `config/fortify.php`. With `MAIL_MAILER=log`, email is written to `storage/logs/laravel.log` rather than delivered to an inbox. Configure an email transport if actual email delivery is needed.

## Running locally

### Laravel development server

```bash
php artisan serve
```

Open `http://localhost:8000`, log in, and choose **Services** in the sidebar. For frontend development, run this in a second terminal:

```bash
npm run dev
```

Alternatively, `composer run dev` starts the project's configured development processes. Stop running development processes with `Ctrl+C`.

### Existing .test domain

If your local PHP environment already serves this repository at `http://customer-appointment-management-system.test`, use that address and set:

```dotenv
APP_URL=http://customer-appointment-management-system.test
```

The domain must be configured by your local server (for example, Herd or Valet); this repository does not create it automatically. Do not also run `php artisan serve` for that domain. Run `npm run dev` while editing frontend assets, or use `npm run build` to generate assets for local use without the frontend development server.

After changing environment configuration, run:

```bash
php artisan config:clear
```

## Tests and quality checks

Run the full quality pipeline used by CI:

```bash
composer ci:check
```

This clears cached configuration, checks PHP formatting with Pint, runs PHPStan/Larastan static analysis, and runs the automated tests. Running only the tests does not run static analysis.

For a quicker test-only run:

```bash
php artisan test --compact
```

Run the service tests:

```bash
php artisan test --compact tests/Feature/ServiceManagementTest.php tests/Feature/ServiceDetailsTest.php
```

`phpunit.xml` configures an in-memory SQLite test database and array-backed session/cache/mail drivers. Tests use temporary data rather than the local development database. Do not override these settings with a real database when running the tests.

Browser layout, mobile navigation, and deletion confirmation also need manual verification. Appointment-history acceptance checks remain pending until the appointment feature exists.

## Scope and delivery

The planned application includes customer CRUD, service CRUD, appointment creation/rescheduling/cancellation/completion, overlap prevention, and chronological appointment views with date/status filters.

Payment processing, SMS notifications, external calendar integrations, customer accounts, multi-location support, elaborate custom UI, hosting, and deployment are outside this contract's scope.

Before final delivery, verify these instructions using a fresh checkout and empty database, update the feature status and known limitations, and confirm all required changes are committed and pushed.

## Project links

- [Repository](https://github.com/pilot-max/customer-appointment-management-system)
- [GitHub Projects - Contract Board](https://github.com/users/pilot-max/projects/5/views/1)
