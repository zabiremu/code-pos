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
- **QR-code customer self-ordering** — each table gets a unique, non-guessable
  link (`Admin\TableController@qr`, printable from the Tables screen); a
  customer scans it, browses the live menu, and submits a cart with no
  login — it lands as pending items on that table's order exactly as if a
  waiter had typed them in, so staff still review and send them to the
  kitchen from the normal POS screen
- **Employee management & attendance** — a full employee profile screen
  (name/email/phone/branch/role/active-status) alongside the existing
  add/remove staff list; every employee gets a self-service clock-in/
  clock-out widget in the header (opening/closing till amounts recorded
  per shift) and admin/manager get an attendance report across everyone,
  filterable by employee or to just who's currently clocked in
- **Brand theme** — a red/charcoal/white visual identity (see
  `tailwind.config.js`'s `primary` color and `resources/css/app.css`'s
  shared component classes) applied consistently across every screen,
  including the installer and a themed pagination view

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
credentials, wrong password, deactivated account, guest redirects, and the
rate limiter locking out repeated bad attempts), every role-gated route
group (a regression suite for the `role:` middleware — see the comment in
`tests/Feature/RoleAccessTest.php` for the exact bug it guards against),
the full order lifecycle (open → add item → send to kitchen → bump through
KDS → bill → pay → close), the orders list's status-tab/search filtering,
QR self-ordering (menu access by token — not by guessable numeric id —
cart submission, and reusing an already-open tab instead of duplicating
it), profile/password self-service, employee management (full-profile edit,
email-uniqueness-ignoring-self, deactivating an account), shift clock-in/
clock-out (including the already-clocked-in / not-clocked-in error paths)
and the admin attendance report's role gating and filters,
`BillingService`'s tax/discount/service-charge math in isolation, the
`.env`-writing helper the installer uses (quoting/escaping — see Security
below), the roles/admin seeder, and the dashboard's revenue/low-stock
figures.

This was written and statically checked (every file passes `php -l`, every
route → controller → view → Blade-component reference was cross-checked,
every migration's foreign keys were verified against creation order) in an
environment that couldn't run `composer install` itself — Packagist was
network-blocked there. Run the suite once against your own `vendor/` and
open an issue (or just fix it — it's your code now) if anything surfaces.

## Security

A quick account of what's actually been checked, not just claimed:

- **Mass assignment** — every model uses an explicit `$fillable` whitelist
  (no `$guarded = []` anywhere), and every controller builds its `create()`/
  `update()` arrays from validated input plus fixed values, never from a raw
  `$request->all()`
- **Login brute-force protection** — `AuthenticatedSessionController` rate-
  limits attempts per email+IP (5/minute) using Laravel's `RateLimiter`
  facade, the same pattern Laravel's own docs recommend in place of the
  deprecated `ThrottlesLogins` trait
- **XSS** — every view uses Blade's auto-escaping `{{ }}`; there is no raw
  `{!! !!}` output of user-supplied data anywhere in `resources/views/`
- **CSRF** — on by default (Laravel's `VerifyCsrfToken` middleware, no
  routes excluded from it) for every state-changing form
- **Passwords** — hashed via `Hash::make()`/the `password` cast, never
  stored or logged in plaintext; the seeded demo admin's password is
  flagged for immediate change on the installer's finish screen
- **`.env` writing** — the installer's database step used to build `.env`
  lines with an unescaped `preg_replace()`, which (a) treats `$1`-style
  substrings in a DB password as regex backreferences and silently mangles
  them, and (b) would corrupt or truncate the file on a value containing a
  space, `#`, or quote. Fixed to quote/escape every value and use
  `preg_replace_callback()` instead — see `InstallController::envValue()`
  and its test, `tests/Unit/InstallEnvValueTest.php`
- **Purchase-code verification** — validates the code's format before
  spending an API call, checks the sale against `ENVATO_ITEM_ID` so a code
  for a different item is rejected, and never trusts a locally-computed
  "valid" flag - it's a live check against Envato's own Author API
- **`APP_DEBUG`** — `.env.example` ships with `APP_ENV=production` and
  `APP_DEBUG=false` by default (it previously defaulted to `local`/`true`,
  which leaks stack traces - file paths, query values, env vars - to
  anyone who hits an error page if a buyer never changes it before going
  live)
- **QR ordering abuse** — the public menu/order routes are throttled
  (`throttle:60,1`) and only ever reachable via a table's own random
  32-character token, never linked from anywhere in the staff-facing app

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
- `app/Http/Controllers/PublicOrderController.php` +
  `resources/views/public/order-menu.blade.php` — the QR self-ordering
  menu; `Admin\TableController@qr` renders the printable QR code
  (client-side, via the `qrcode` npm package - no new Composer dependency)
- `app/Http/Controllers/ProfileController.php` +
  `resources/views/profile/edit.blade.php` — self-service profile/password
  editing, open to any authenticated role
- `app/Http/Controllers/ShiftController.php` — self-service clock-in/
  clock-out, open to any authenticated role, backed by `User::activeShift()`
  and the `shifts` table (already in the original schema for cash-drawer
  reconciliation, but unwired until now); the header widget lives in
  `resources/views/components/layouts/admin.blade.php`
- `app/Http/Controllers/Admin/ShiftController.php` +
  `resources/views/admin/shifts/index.blade.php` — the admin/manager
  attendance report over every employee's shifts, filterable by employee
  or to just who's currently clocked in
- `app/Http/Controllers/Admin/StaffController.php` +
  `resources/views/admin/staff/show.blade.php` — the full employee profile/
  edit screen (name/email/phone/branch/role/active-status, plus that
  employee's own shift history), reached from the staff list
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
| qrcode | MIT |
| vite | MIT |

No third-party fonts, icon packs, images, or other bundled binary assets
are used — the brand mark in the sidebar/login/installer is a hand-written
inline SVG, not a licensed icon set.

## Still open before this is submission-ready

Honestly, in priority order:

- **Run the real test suite once, for real.** Every check in this repo's
  history (including this pass's) was done by static analysis — `php -l`,
  and custom scripts cross-checking route/view/component references and
  Blade directive balance — because Packagist is network-blocked in the
  environment this was built in, so `composer install` has never actually
  been run here. It *has* been run successfully against a live cPanel
  deploy previously (see commit history for the bugs that surfaced and got
  fixed that way), but that was reactive, not `php artisan test` catching
  things before they shipped. Run `composer install && php artisan test`
  yourself before submitting - if anything surfaces, it's cheaper to find
  now than after a reviewer or buyer does.
- **CodeCanyon preview assets** (can't be produced from a hand-built repo -
  need an actual running instance): a portrait feature-preview image, a
  590×300 thumbnail, and a 5-8 minute walkthrough video. Screenshot, at
  minimum: the dashboard, the orders list (all four status tabs), an open
  order's item screen, the KDS board, a generated bill, the floor/tables
  view, the QR ordering menu on an actual phone, the QR print page, the
  installer's purchase-code step, and the profile/password screen. Real
  screenshots of a real screen beat a mockup every time on this platform.
- **Manual QA on the full order lifecycle end to end**, on a phone for the
  QR ordering flow specifically (open a table's QR link, order, confirm it
  shows up correctly as a pending item back on the POS side, send it to
  the kitchen, bump it through KDS, bill it, pay it).
- **Browser/device compatibility** — Envato's own review checklist asks
  for this explicitly; hasn't been checked here at all.
- Table drag-and-drop floor-plan editor (current admin view is a card-based
  list with live status colors, not a freeform drag canvas)
- Multi-branch (deliberately deferred — see the build plan)
- The Envato compliance pass itself: run through the checklist in the build
  plan against this actual code (debug-mode error check, final packaging
  into `main-files.zip`, listing page copy/screenshots)
- A live demo deployment with the test credentials shown on the listing page
  (there's a `.github/workflows/main.yml` FTP deploy workflow already set up
  for this — see repo secrets)
- Envato's stated rejection criteria explicitly flag items "too similar to
  existing catalog items" without a standout feature - QR self-ordering is
  this repo's answer to that, but a second differentiator (e.g. a WhatsApp/
  SMS order-ready notification, or a bKash/Nagad/SSLCommerz payment
  integration for the BD market specifically) would meaningfully strengthen
  the listing further

## License
Proprietary — intended for commercial distribution via Envato CodeCanyon.
Not open source. See `LICENSE.txt`.
