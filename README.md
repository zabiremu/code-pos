# Restaurant POS (CodeCanyon Edition)

A Laravel-based Point-of-Sale system built for restaurants and cafes,
targeting submission to Envato CodeCanyon.

**Status:** The full Laravel 12 skeleton, phases 1–6 of the build plan, and
a red/white brand theme are all in this repo — schema, roles, menu & floor
management, order taking + KDS, billing, reports/stock, the web installer,
Envato purchase-code verification, and a hand-built login (staff accounts
are created by an admin — see below — so there's no self-registration
flow). This has been run end-to-end against a live cPanel deploy (real
`composer install`, real `npm run build`, real MySQL) — see commit history
for every bug that surfaced along the way and its fix. A PHPUnit test suite
covers the core flows; see Testing below for how to run it against your own
`vendor/`.

Full build plan (feature modules, database schema, architecture, Envato
compliance checklist, installer design, packaging, milestones) lives in the
project's Claude Docs plan.

## Features

- **Menu management** — categories (with parent/child nesting), menu items
  with per-item price and tax-rate overrides, item variants, modifier
  groups (required / max-selectable), and a recipe-style ingredient list
  per item for stock deduction
- **Floor & table management** — floors, tables with seat counts and a
  live status (free / occupied / reserved)
- **Order taking** — dine-in / takeaway / delivery, line items with
  modifiers and notes, a per-item status lifecycle (pending → sent →
  preparing → ready → served), send-to-kitchen in one action
- **Kitchen Display System** — station-filterable ticket board, live
  updates over Laravel Echo/Pusher when configured, falls back to
  polling every 5s on shared hosting with no broadcast service
- **Billing** — tax, service charge, and percent/fixed discounts; split
  billing by a subset of an order's items; multiple payments per bill
  (cash / card / mobile wallet / other) with automatic paid / partially
  paid status
- **Reports** — daily sales with top-selling items, low-stock ingredient
  alerts, an admin dashboard with today's orders/revenue at a glance
- **Role-based access** — five roles (admin, manager, cashier, waiter,
  kitchen) via spatie/laravel-permission, each gated to the routes that
  role actually needs
- **Web installer** — a five-step wizard (requirements check → Envato
  purchase-code verification → database setup → done) so a buyer never
  touches the command line; locks itself shut after first run
- **Brand theme** — a red/white visual identity (see `tailwind.config.js`'s
  `primary` color and `resources/css/app.css`'s shared component classes)
  applied consistently across every screen, including the installer and
  a themed pagination view

## Stack
- Laravel 12.x on PHP 8.2+
- MySQL 8 (production) / SQLite works for local dev or tests
- Blade + Alpine.js + Tailwind CSS — red/white brand theme (see Features)
- spatie/laravel-permission (roles), Laravel Sanctum (API auth)
- Laravel Echo + Pusher/self-hosted Soketi for the Kitchen Display System,
  with a polling fallback for shared-hosting installs

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # seeds roles, a demo admin (admin@example.com / password), and a sample menu
php artisan serve
```

`npm install && npm run build` isn't required to get running — the compiled
`public/build/` output is committed to this repo (see below) — but run it
if you change anything under `resources/`.

> `laravel/framework` targets `^12.0`, not 11 — every 11.x release up to at
> least 11.56.1 has unresolved security advisories that will hard-block
> `composer install` under a policy-enforcing Composer setup (`Your
> requirements could not be resolved... affected by security advisories`).
> If you ever see that error, the fix is either bumping the constraint like
> this, or `composer config policy.advisories.block false` as a stopgap.

That's the whole thing — `bootstrap/app.php` already wires up the `role` and
`redirect.if.installed` middleware aliases and loads `routes/web.php`,
`routes/auth.php`, and `routes/install.php`. `config/services.php` already
has the `envato.item_id` entry `PurchaseCodeService` reads.

For the web installer (`/install`) instead of the CLI migrate above: it's
locked shut once `storage/installed.lock` exists, so it only runs on a truly
fresh deploy. Delete that file to re-open it (e.g. after cloning fresh onto
a server).

### Shared hosting: no "/public/" in the URL

If your host's Document Root points at this project's root folder instead of
its `public/` subfolder (common on cPanel shared hosting, and not always
something you can change), the repo ships a root-level `.htaccess` that
transparently routes every request into `public/` at the web-server level —
so `https://yourdomain.com/login` works instead of
`https://yourdomain.com/public/login`, with no Document Root change needed.
If your Document Root *is* already set to `public/`, this file is inert and
safe to leave in place (or delete).

> `composer install` and `npm run build` have both been run against this
> repo and it boots end-to-end on a live cPanel deploy (see commit history)
> — `vendor/` still isn't committed (install it yourself), but
> `public/build/` (the compiled Tailwind/Alpine output) *is* committed, since
> most shared hosts have no Node.js available to run `npm run build` on the
> server itself. Re-run `npm run build` and commit the new `public/build/`
> output any time you change something under `resources/`.

## Testing

```bash
composer install   # test dependencies (phpunit, mockery, faker) are in require-dev
php artisan test
# or: vendor/bin/phpunit
```

Tests run against an in-memory SQLite database (`phpunit.xml`), so they
never touch your real `.env`/MySQL setup. Coverage includes: login (valid
credentials, wrong password, deactivated account, guest redirects), every
role-gated route group (a regression suite for the `role:` middleware —
see the comment in `tests/Feature/RoleAccessTest.php` for the exact bug it
guards against), the full order lifecycle (open → add item → send to
kitchen → bump through KDS → bill → pay → close), `BillingService`'s tax/
discount/service-charge math in isolation, the roles/admin seeder, and the
dashboard's revenue/low-stock figures.

This was written and statically checked (every file passes `php -l`, every
route → controller → view → Blade-component reference was cross-checked,
every migration's foreign keys were verified against creation order) in an
environment that couldn't run `composer install` itself — Packagist was
network-blocked there. Run the suite once against your own `vendor/` and
open an issue (or just fix it — it's your code now) if anything surfaces.

## What's here

- `database/migrations/` — full schema: users/roles/permissions, branches,
  floors, tables, menu categories/items/variants/modifiers, ingredients,
  orders, bills, payments, shifts, stock movements, discounts
- `app/Models/` — matching Eloquent models with relationships
- `app/Enums/Role.php`, `database/seeders/RolesAndAdminSeeder.php`,
  `database/seeders/DemoMenuSeeder.php` — the five POS roles, a demo admin,
  and a sample menu/floor plan so a fresh install has something to click
  through immediately
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` +
  `routes/auth.php` — login/logout; staff accounts are created via
  `Admin\StaffController`, not self-registered
- `app/Http/Controllers/Admin/` — categories, menu items, modifier groups,
  floors, tables, staff, reports (sales + low stock), dashboard
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
- `resources/css/app.css`, `tailwind.config.js` — the red/white brand theme:
  shared button/card/badge/input component classes and the `primary` color
  scale, used consistently by every view
- `resources/views/components/layouts/` — the shared admin layout (desktop
  sidebar + mobile drawer, both rendering `sidebar-nav.blade.php` so they
  can't drift apart) and the installer's shared shell with its step indicator
- `resources/views/vendor/pagination/tailwind.blade.php` — Laravel's default
  pagination view re-themed to the brand color instead of its default indigo
- `bootstrap/`, `public/`, `config/`, `routes/console.php`,
  `resources/css`, `resources/js`, `vite.config.js`, `tailwind.config.js` —
  the framework skeleton itself
- `tests/` — see Testing above

## Third-party packages & licenses

Everything this project depends on is MIT-licensed (Envato's compliance
checklist asks for this to be documented, so a buyer/reviewer doesn't have
to trace it themselves):

| Package | License |
|---|---|
| laravel/framework | MIT |
| laravel/sanctum | MIT |
| laravel/tinker | MIT |
| spatie/laravel-permission | MIT |
| barryvdh/laravel-dompdf | MIT |
| maatwebsite/excel | MIT |
| alpinejs | MIT |
| tailwindcss | MIT |
| laravel-echo | MIT |
| pusher-js | MIT |
| vite | MIT |

No third-party fonts, icon packs, images, or other bundled binary assets
are used — the brand mark in the sidebar/login/installer is a hand-written
inline SVG, not a licensed icon set.

## Still open before this is submission-ready

- Table drag-and-drop floor-plan editor (current admin view is a card-based
  list with live status colors, not a freeform drag canvas)
- Multi-branch (deliberately deferred — see the build plan)
- The Envato compliance pass itself: run through the checklist in the build
  plan against this actual code (debug-mode error check, final packaging
  into `main-files.zip`, listing page copy/screenshots)
- A live demo deployment with the test credentials shown on the listing page
  (there's a `.github/workflows/main.yml` FTP deploy workflow already set up
  for this — see repo secrets)

## License
Proprietary — intended for commercial distribution via Envato CodeCanyon.
Not open source. See `LICENSE.txt`.
