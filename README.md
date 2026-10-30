# Customer & Appointment Management System

An owner-only internal application for a single-location service business, built as a simulated US$300 fixed-price freelance contract with a 14-day scope.

Built with Laravel 13, Livewire 4, Flux UI, and Tailwind CSS. GitHub Projects is the task backlog.

## Current progress

- Owner login/logout and an interactive owner-account creation command.
- Service management: list, create, view, edit, validate, and soft-delete services.
- Customer management: list, create, view, edit, validate, and soft-delete customers.
- Sidebar navigation, page titles, clickable record details, and success toasts for customer actions.
- Appointment management: book, view, edit, reschedule, cancel, and complete appointments, with automatic end times and overlap prevention.
- Chronological appointment lists with date/status filters, an upcoming-and-overdue option, and colour-coded status badges.
- Booked service details are preserved when the service catalogue changes, including when the original service is soft-deleted.
- Automated customer, service, and appointment feature tests.

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

Open `http://localhost:8000`, log in, and choose **Customers**, **Services**, or **Appointments** in the sidebar. For frontend development, run this in a second terminal:

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

## Customer management

Use **Customers** in the sidebar to create a customer, open their details by clicking the contact information, edit their details, or delete them after confirmation.

- Name, phone number, and email address are required; names and email addresses support up to 255 characters.
- Email addresses must have a valid format. Customers may share an email address or phone number.
- Phone numbers must contain 7 to 15 digits and may include a leading `+`, spaces, parentheses, dots, and hyphens. Formatting and leading zeros are preserved. This checks format only, not whether the number exists; extensions are not currently supported.
- Successful creation, editing, and deletion display a toast notification.
- Deletion is soft deletion: the customer disappears from the active list, but the database record remains. There is currently no restore interface.

## Appointment management

Use **Appointments** in the sidebar, then **Book appointment** to choose an existing customer, service, and start date/time. Create a customer and service first if none are available.

- End times are calculated from the booked service duration.
- Dates and times represent the company's local wall-clock time. Input is stored and displayed unchanged, with no user-specific timezone conversion or daylight-saving adjustment.
- Appointments cannot overlap another scheduled or completed appointment. Back-to-back appointments are allowed; cancelled appointments do not reserve time.
- Click an appointment to view its details. Scheduled appointments can be edited, rescheduled, cancelled, or marked completed. Completed and cancelled appointments cannot be edited or reopened through the interface.
- Each booking stores the service name, price, and duration at booking time. Editing the service catalogue does not change existing bookings. Rescheduling with the same service retains those details; selecting a different service takes a new snapshot.
- Soft-deleted customers and services remain available on their existing appointments, but cannot be selected for new bookings.
- The list is ordered by start time and paginated. By default it shows upcoming appointments plus overdue scheduled appointments. Turn off **Upcoming and overdue** to include all earlier bookings, or select a date to view bookings for that date regardless of the upcoming setting. Status filtering can be combined with either option.
- Scheduled appointments whose end time has passed display an additional amber **Overdue** badge on the list and details page. This is calculated when the page renders; refresh an open page to update it as time passes. Completed and cancelled appointments are never overdue, and the saved status remains unchanged.
- Status badges use blue for scheduled, green for completed, and grey for cancelled appointments. Successful appointment actions display toast notifications.

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

Run the customer tests:

```bash
php artisan test --compact tests/Feature/CustomerManagementTest.php
```

Run the appointment tests:

```bash
php artisan test --compact tests/Feature/AppointmentManagementTest.php
```

Customer coverage includes creation, editing, details, alphabetical listing, validation boundaries and phone formats, shared contact details, guest restrictions, soft deletion, missing/deleted records, HTML escaping, navigation links, and success-toast events.

Appointment coverage includes authenticated access, booking and required fields, date validation, unchanged wall-clock times, automatic end times, overlap rejection, back-to-back bookings, released cancelled slots, rescheduling, preserved service snapshots and soft-deleted relationships, cancellation/completion, terminal-status restrictions, chronological ordering, and date/status filters.

`phpunit.xml` configures an in-memory SQLite test database and array-backed session/cache/mail drivers. Tests use temporary data rather than the local development database. Do not override these settings with a real database when running the tests.

Browser layout, mobile navigation, toast appearance, status colours in light/dark mode, and confirmation dialogs also need manual verification. Before delivery, manually walk through booking, overlap rejection, rescheduling, cancellation, completion, and filtering.

## Scope and delivery

The application includes customer CRUD, service CRUD, appointment creation/rescheduling/cancellation/completion, overlap prevention, and chronological appointment views with date/status filters.

Payment processing, SMS notifications, external calendar integrations, customer accounts, multi-location support, elaborate custom UI, hosting, and deployment are outside this contract's scope.

Before final delivery, verify these instructions using a fresh checkout and empty database, update the feature status and known limitations, and confirm all required changes are committed and pushed.

## Project links

- [Repository](https://github.com/pilot-max/customer-appointment-management-system)
- [GitHub Projects - Contract Board](https://github.com/users/pilot-max/projects/5/views/1)
