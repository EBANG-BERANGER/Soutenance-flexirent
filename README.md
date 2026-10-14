# FlexiRent

FlexiRent is a web-based car rental marketplace for Kigali, Rwanda. Partners (individual owners or agencies) list vehicles; clients browse the catalogue, book, and pay; admins moderate partners, vehicles, payments, and disputes.

Built with Laravel 12, Blade, and Bootstrap 5. No frontend build step: CSS and JS are plain files in `public/`, with Alpine.js loaded from a CDN for the small amount of client-side interactivity (filters, live search).

## Stack

- PHP 8.2+, Laravel 12
- MySQL
- Blade templates, Bootstrap 5, Alpine.js (CDN, no npm/Vite)
- `barryvdh/laravel-dompdf` for generated rental contracts
- Pure GD (bundled with PHP) for image resizing on upload, no imaging package
- PHPUnit for tests

## Installation

```powershell
composer install
copy .env.example .env
php artisan key:generate
```

Edit `.env`:
- Set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for a local MySQL database.
- Set `MAIL_*` to real SMTP credentials (Gmail, Mailtrap, etc.) if you want verification/notification emails to actually send.
- Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` for the real administrator account created by the seeder below. These are intentionally left out of `.env.example` since they are a genuine credential, not a demo one.

```powershell
php artisan migrate
php artisan storage:link
php artisan db:seed
php artisan serve
```

`storage:link` must succeed and produce a real symlink/junction at `public/storage`; on Windows this requires the terminal to have permission to create symlinks (running as Administrator, or Developer Mode enabled). Without it, vehicle photos and profile pictures will 404.

### Demo data

`php artisan db:seed` runs, in order:

| Seeder | Creates |
|---|---|
| `AdminSeeder` | The real admin account, from `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env` |
| `CategorySeeder` | Vehicle categories |
| `DemoFleetSeeder` | ~15 demo partners (agencies and individuals) and ~30 vehicles across every category and price band |
| `DemoClientsSeeder` | ~30 demo client accounts with realistic Gabon/Rwanda/Chad names |
| `DemoReservationsSeeder` | A spread of past, current, and upcoming reservations against the demo fleet |
| `UatDemoSeeder` | 2 easy-to-remember demo accounts (below), for UAT testers who shouldn't have to hunt through the ~60 generated accounts above |

Demo client and partner accounts all share the password `Demo@2026!`. The bulk of them (`DemoClientsSeeder`, `DemoFleetSeeder`) have emails generated from each demo name (e.g. `jean.habimana@gmail.com`) — check the seeder output or the `users` table for the exact list. For UAT, hand testers these two instead:

| Role | Email | Password |
|---|---|---|
| Client | `flexirent.demo.client@gmail.com` | `Demo@2026!` |
| Partner | `flexirent.demo.partner@gmail.com` | `Demo@2026!` |

(Admin access is the real `ADMIN_EMAIL`/`ADMIN_PASSWORD` account from `.env`, not a demo one — don't hand that out to UAT testers.)

## Roles

- **Client** — browses the catalogue, books vehicles, pays, cancels/reviews rentals. Has a reliability score (see "Notable systems" below) that gates payment options.
- **Partner** — lists and manages their own vehicles, sets pricing and minimum rental days, views their own financial history, reports traffic violations against past renters. Registration requires a supporting document and is approved by an admin.
- **Admin** — approves/suspends partners and vehicles, reviews payments, views platform-wide financial history (per-vehicle and per-partner, exportable as CSV), manages site settings (cover photo, etc.).

## Notable systems

- **Financial history** — every screen that shows money (per-vehicle history, per-partner history, partner dashboard) is built on a single query, `Reservation::financialHistoryQuery($vehicleIds, $from, $to, $client)`, so totals shown in the UI and totals in CSV exports can never drift apart.
- **Reliability score** — clients earn/lose points for completing rentals vs. cancelling late (`app/Models/ReliabilityEvent.php`); a low score restricts them to full upfront payment instead of a deposit.
- **Vehicle recommendations** — `Vehicle::similarVehicles()` is simple content-based scoring (category, price band, car type, propulsion), not an ML model; see `app/Models/Vehicle.php`.
- **Traffic violations** — partners can report a violation against a past reservation at any time, even long after the rental ended; the client is notified. See `app/Models/Violation.php`.
- **Price/rate snapshotting** — a reservation freezes the vehicle's rate at booking time (`price_per_day_snapshot`, etc.) so a later price change by the partner never retroactively changes what an existing client owes.

## Folder structure highlights

```
app/Http/Controllers/   One controller per resource (Vehicle, Reservation, Partner, Admin, ...)
app/Models/              Eloquent models; Reservation and Vehicle carry most of the domain logic
app/Support/             Small stateless helpers (ImageOptimizer, SiteCover)
app/Console/Commands/    Scheduled jobs: CompleteFinishedReservations (daily), ExpireUnpaidReservations (hourly)
resources/views/         Blade templates, grouped by area (vehicles/, admin/, partner/, auth/, components/)
database/seeders/        Demo data generation, see above
database/migrations/     Standard Laravel migrations; a few use raw `DB::statement(ALTER TABLE ...)`
                          instead of Blueprint's ->change() because doctrine/dbal is not installed
tests/Feature/           Feature tests exercising booking, cancellation, payments, admin flows, etc.
public/css, public/js    Plain CSS/JS, no build step
```

## Known compromises

These are deliberate simplifications for this environment, not oversights:

- **`QUEUE_CONNECTION=sync`** — no queue worker process runs in this dev environment, so `WelcomeMail`, `TransactionalMail`, and `ContractMail` (all `ShouldQueue`) are dispatched synchronously instead of through a real queue. In a production deployment, switch this to `database` (or Redis/SQS) and run `php artisan queue:work` as a persistent process; the Mailables don't need to change.
- **Mobile money / SMS gateway variables** (`MTN_MOMO_*`, `AIRTEL_MONEY_*`, `SMS_GATEWAY_*`) — present in `.env`/`.env.example` but not wired to any controller. Payments are currently recorded manually by an admin after off-platform confirmation. These variables are placeholders for that future integration, not dead config.
- **No `doctrine/dbal`** — migrations that alter an existing column's type (see `database/migrations/2026_08_21_000006_make_financial_columns_unsigned.php`) use raw `DB::statement('ALTER TABLE ...')` rather than Blueprint's `->change()`, which requires that package. Kept out to avoid an extra dependency for a handful of migrations.
- **Scheduled commands need a real cron entry** — `CompleteFinishedReservations` and `ExpireUnpaidReservations` are registered in `routes/console.php` via Laravel's scheduler, but nothing runs `php artisan schedule:run` automatically in this dev environment. In production, add a single cron entry (`* * * * * php artisan schedule:run`) or an equivalent scheduled task; until then, run them manually when needed.

## Deployment notes (Railway UAT)

FlexiRent is set up to deploy on Railway (PHP + MySQL) for a pre-soutenance UAT round with real users. A few things to know before doing that:

- **Environment variables**: see the commented "Railway production" block at the bottom of `.env.example` for the full list (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, the MySQL plugin's `DB_*` variables, real `MAIL_*` credentials, `ADMIN_EMAIL`/`ADMIN_PASSWORD`). Set these as actual Railway environment variables, not by uploading a `.env` file.
- **`APP_DEBUG` must be `false` in production** — Laravel defaults to `false` if the variable is unset at all (`config/app.php`), but set it explicitly on Railway anyway so a real UAT tester never sees a raw PHP stack trace.
- **HTTPS**: `AppServiceProvider::boot()` calls `URL::forceScheme('https')` when `APP_ENV=production`, and `bootstrap/app.php` trusts Railway's reverse proxy (`trustProxies(at: '*')`) so Laravel correctly detects the request as secure and generates `https://` links instead of `http://`.
- **Health check**: Railway can point its health check at `GET /up` (Laravel's built-in health route, registered in `bootstrap/app.php`) to know the app booted successfully.
- **File storage does not persist across redeploys.** Vehicle photos, profile photos, cover photos, and generated contract PDFs are all written to local disk (`storage/app/public`, served via the `public/storage` symlink). Railway's filesystem is ephemeral per deploy: every redeploy (and every restart of the container) wipes anything written after the image was built, since `storage/` isn't part of the build. For a short UAT round (a few days, no redeploys in between) this is fine — uploaded photos and contracts survive as long as the container keeps running. For anything longer, or once redeploys resume, migrate the `public` disk to an S3-compatible store (Railway Buckets, or any S3-compatible provider) via Laravel's built-in `s3` filesystem driver; no application code needs to change beyond swapping the `FILESYSTEM_DISK` config and adding the `league/flysystem-aws-s3-v3` package, since uploads already go through `Storage::disk(...)` / `ImageOptimizer` rather than raw file paths.
- **Migrations**: `php artisan migrate --force` runs cleanly against an empty database (verified locally via the full test suite, which runs every migration against a fresh `flexirent_test` database on every run). `--force` is required because `APP_ENV=production` otherwise prompts for confirmation, which has no terminal to answer it during an automated deploy.
- **Queue and scheduler**: both are already documented under "Known compromises" above — `QUEUE_CONNECTION=sync` means no separate worker process is needed for UAT, and the two scheduled commands can be run manually (`php artisan app:complete-finished-reservations`, `php artisan app:expire-unpaid-reservations`) if Railway's cron isn't configured yet.

## Testing

```powershell
php artisan test
```

Uses `RefreshDatabase` against a real test database connection (configure it in `.env.testing` if you don't want tests touching your dev database). Covers booking, cancellation, modification, payment confirmation, partner registration, reliability scoring, vehicle recommendations, violations, and financial history.
