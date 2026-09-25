# Restaurant POS (CodeCanyon Edition)

A Laravel-based Point-of-Sale system built for restaurants and cafes,
targeting submission to Envato CodeCanyon.

**Status:** The full Laravel 11 skeleton plus phases 1–6 of the build plan
are in this repo — schema, roles, menu & floor management, order taking +
KDS, billing, reports/stock, the web installer, Envato purchase-code
verification, and a hand-built login (staff accounts are created by an
admin — see below — so there's no self-registration flow). What's missing
is `vendor/` and `node_modules/` (never committed — see Setup) and real
execution testing (see the note at the bottom of Setup).

Full build plan (feature modules, database schema, architecture, Envato
compliance checklist, installer design, packaging, milestones) lives in the
project's Claude Docs plan.

## Stack
- Laravel 11.x on PHP 8.2+
- MySQL 8 (production) / SQLite works for local dev or tests
- Blade + Alpine.js + Tailwind CSS
- spatie/laravel-permission (roles), Laravel Sanctum (API auth)
- Laravel Echo + Pusher/self-hosted Soketi for the Kitchen Display System,
  with a polling fallback for shared-hosting installs

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"   # confirm it doesn't overwrite config/permission.php's 'models' block — reapply if it does
php artisan migrate --seed   # seeds roles, a demo admin (admin@example.com / password), and a sample menu
npm install && npm run build
php artisan serve
```

That's the whole thing — `bootstrap/app.php` already wires up the `role` and
`redirect.if.installed` middleware aliases and loads `routes/web.php`,
`routes/auth.php`, and `routes/install.php`. `config/services.php` already
has the `envato.item_id` entry `PurchaseCodeService` reads.

For the web installer (`/install`) instead of the CLI migrate above: it's
locked shut once `storage/installed.lock` exists, so it only runs on a truly
fresh deploy. Delete that file to re-open it (e.g. after cloning fresh onto
a server).

> I couldn't run `composer install` or `npm install` from the sandbox this
> was built in — its network policy blocks Packagist — so this is written
> out carefully (every PHP file passes `php -l`, `composer.json` /
> `package.json` are valid JSON) but not execution-tested against a real
> `vendor/`. Run it through once end to end and tell me what breaks — my
> best guesses for rough edges: exact `spatie/laravel-permission` config
> keys drifting from what `vendor:publish` generates for whatever version
> `composer install` resolves, and Tailwind/Alpine class or import paths
> once real builds run through Vite.

## What's here

- `database/migrations/` — full schema: branches, floors, tables, menu
  categories/items/variants/modifiers, ingredients, orders, bills, payments,
  shifts, stock movements, discounts
- `app/Models/` — matching Eloquent models with relationships
- `app/Enums/Role.php`, `database/seeders/RolesAndAdminSeeder.php`,
  `database/seeders/DemoMenuSeeder.php` — the five POS roles, a demo admin,
  and a sample menu/floor plan so a fresh install has something to click
  through immediately
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` +
  `routes/auth.php` — login/logout; staff accounts are created via
  `Admin\StaffController`, not self-registered
- `app/Http/Controllers/Admin/` — categories, menu items, modifier groups,
  floors, tables, staff, reports (sales + low stock)
- `app/Http/Controllers/POS/` — order taking (`OrderController`,
  `OrderItemController`), billing (`BillController`, `PaymentController`)
- `app/Http/Controllers/KDS/TicketController.php` — kitchen ticket board,
  grouped by station, with `App\Events\OrderItemStatusUpdated` broadcasting
  status changes (polling fallback baked into the view)
- `app/Services/BillingService.php` — bill generation (incl. split billing
  by item), tax/discount/service-charge math, payment recording
- `app/Services/StockService.php` + `app/Observers/OrderItemObserver.php` —
  ingredient stock deducted automatically when an item is marked served
- `app/Http/Controllers/Install/InstallController.php` +
  `app/Services/PurchaseCodeService.php` — the web installer wizard
  (requirements check → purchase code → database → migrate/seed → finish)
- `bootstrap/`, `public/`, `config/`, `routes/console.php`,
  `resources/css`, `resources/js`, `vite.config.js`, `tailwind.config.js` —
  the framework skeleton itself
- `tests/` — a starter `TestCase` and one example feature test

## Still open before this is submission-ready

- Table drag-and-drop floor-plan editor (current admin view is a simple list)
- Multi-branch (deliberately deferred — see the build plan)
- The Envato compliance pass itself: run through the checklist in the build
  plan against this actual code (debug-mode error check, third-party asset
  license audit, final packaging into `main-files.zip`)
- A live demo deployment with the test credentials shown on the listing page
  (there's a `.github/workflows/main.yml` FTP deploy workflow already set up
  for this — currently blocked on FTP login credentials, see repo secrets)

## License
Proprietary — intended for commercial distribution via Envato CodeCanyon.
Not open source.
