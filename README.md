# POS (CodeCanyon Edition)

A Laravel-based, general-purpose Point-of-Sale system for retail and small
businesses, targeting submission to Envato CodeCanyon.

**Status:** originally built as a restaurant/cafe POS (table management,
kitchen display, QR self-ordering); as of `2.0.0` it has been converted into
a generic retail POS aimed at a much broader buyer base — see the Changelog
for exactly what changed and why. The full Laravel 12 skeleton, schema,
roles, product catalog, sale taking + billing, reports/stock, the web
installer, Envato purchase-code verification, and a hand-built login (staff
accounts are created by an admin — see below — so there's no
self-registration flow) are all in this repo. A PHPUnit test suite covers
the core flows; see Testing below for how to run it against your own
`vendor/`.

## Features

- **Product catalog** — categories (with parent/child nesting), products
  with SKU, per-product price and tax-rate overrides, and optional
  per-product stock tracking (quantity + low-stock threshold)
- **Sales** — a simple cart-style sale (add products, adjust quantities,
  remove a line) that a cashier opens, bills, and closes
- **Billing** — tax, service charge, and percent/fixed discounts; split
  billing by a subset of a sale's items; multiple payments per bill
  (cash / card / mobile wallet / other) with automatic paid / partially
  paid status
- **Stock** — optional per-product stock tracking, deducted automatically
  the moment an item is added to a sale (and restored if removed before
  billing); a low-stock report and dashboard count
- **Reports** — daily sales with top-selling products, low-stock alerts,
  an admin dashboard with today's sales/revenue at a glance
- **Role-based access** — three roles (admin, manager, cashier) via
  spatie/laravel-permission, each gated to the routes that role actually
  needs
- **Web installer** — a five-step wizard (requirements check → Envato
  purchase-code verification → database setup → done) so a buyer never
  touches the command line; locks itself shut after first run
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

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # seeds roles, a demo admin (admin@example.com / password), and a sample catalog
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

> `composer install` and `npm run build` have both been run against the
> pre-pivot (`1.x`, restaurant-specific) version of this repo on a live
> cPanel deploy (see commit history for the bugs that surfaced and got fixed
> that way). The `2.0.0` conversion to a generic POS itself has only been
> checked statically (see Testing) — re-run both before submitting.
> `vendor/` still isn't committed (install it yourself), but `public/build/`
> (the compiled Tailwind/Alpine output) *is* committed, since most shared
> hosts have no Node.js available to run `npm run build` on the server
> itself. Re-run `npm run build` and commit the new `public/build/` output
> any time you change something under `resources/`.

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
`tests/Feature/RoleAccessTest.php` for the exact bug it guards against), the
full sale lifecycle (open → add item → bill → pay → close), the sales
list's status-tab/search filtering, profile/password self-service, employee
management (full-profile edit, email-uniqueness-ignoring-self, deactivating
an account), shift clock-in/clock-out (including the already-clocked-in /
not-clocked-in error paths) and the admin attendance report's role gating
and filters, `BillingService`'s tax/discount/service-charge math in
isolation, the `.env`-writing helper the installer uses (quoting/escaping —
see Security below), the roles/admin seeder, and the dashboard's
revenue/low-stock figures.

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

## What's here

- `database/migrations/` — full schema: users/roles/permissions, branches,
  categories/products, sales, sale items, bills, payments, shifts, stock
  movements, discounts
- `app/Models/` — matching Eloquent models with relationships
- `app/Enums/Role.php`, `database/seeders/RolesAndAdminSeeder.php`,
  `database/seeders/DemoProductSeeder.php` — the three POS roles, a demo
  admin, and a sample catalog so a fresh install has something to click
  through immediately
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` +
  `routes/auth.php` — login/logout; staff accounts are created via
  `Admin\StaffController`, not self-registered
- `app/Http/Controllers/Admin/` — categories, products, staff, reports
  (sales + low stock), dashboard
- `app/Http/Controllers/POS/` — sale taking (`SaleController`,
  `SaleItemController`), billing (`BillController`, `PaymentController`)
- `app/Services/BillingService.php` — bill generation (incl. split billing
  by item), tax/discount/service-charge math, payment recording
- `app/Services/StockService.php` — per-product stock deducted the moment
  an item is added to a sale, restored if it's removed before billing
- `app/Http/Controllers/Install/InstallController.php` +
  `app/Services/PurchaseCodeService.php` — the web installer wizard
  (requirements check → purchase code → database → migrate/seed → finish)
- `app/Http/Controllers/ProfileController.php` +
  `resources/views/profile/edit.blade.php` — self-service profile/password
  editing, open to any authenticated role
- `app/Http/Controllers/ShiftController.php` — self-service clock-in/
  clock-out, open to any authenticated role, backed by `User::activeShift()`
  and the `shifts` table; the header widget lives in
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
| vite | MIT |

No third-party fonts, icon packs, images, or other bundled binary assets
are used — the brand mark in the sidebar/login/installer is a hand-written
inline SVG, not a licensed icon set.

## Still open before this is submission-ready

Honestly, in priority order:

- **Run the real test suite once, for real**, specifically against the
  `2.0.0` generic-POS conversion — every check on this branch so far
  (including the pivot itself) was done by static analysis (`php -l`, and
  manual route/view/component/foreign-key cross-checking), because
  Packagist is network-blocked in the environment this was built in, so
  `composer install` has never actually been run against this version.
  Run `composer install && php artisan test` yourself before submitting —
  if anything surfaces, it's cheaper to find now than after a reviewer or
  buyer does.
- **CodeCanyon preview assets** (can't be produced from a hand-built repo -
  need an actual running instance): a portrait feature-preview image, a
  590×300 thumbnail, and a 5-8 minute walkthrough video. Screenshot, at
  minimum: the dashboard, the sales list (all status tabs), an open sale's
  item screen, a generated bill, the product catalog, the installer's
  purchase-code step, and the profile/password screen. Real screenshots of
  a real screen beat a mockup every time on this platform.
- **Manual QA on the full sale lifecycle end to end** (open a sale, add
  items, confirm stock deducts, bill it, pay it, close it).
- **Browser/device compatibility** — Envato's own review checklist asks
  for this explicitly; hasn't been checked here at all.
- **A product edit screen** — `Admin\ProductController::update()` exists
  and is tested at the HTTP layer, but there's no edit UI wired to it yet
  (only create + list + delete); this gap pre-dates the pivot.
- **Product variants/options** (e.g. size/color) were deliberately dropped
  in the generic-POS conversion for simplicity — worth adding back as a
  generic "product options" feature if buyer demand calls for it (see
  Changelog `2.0.0`)
- Multi-branch (deliberately deferred — see the build plan)
- The Envato compliance pass itself: run through the checklist in the build
  plan against this actual code (debug-mode error check, final packaging
  into `main-files.zip`, listing page copy/screenshots)
- A live demo deployment with the test credentials shown on the listing page
  (there's a `.github/workflows/main.yml` FTP deploy workflow already set up
  for this — see repo secrets)
- A standout differentiator feature for the generic-POS listing (the
  restaurant edition's QR self-ordering doesn't apply anymore) — e.g. a
  barcode-scanner-friendly SKU lookup, CSV product import/export, or a
  receipt-printer integration would meaningfully strengthen the listing

## License
Proprietary — intended for commercial distribution via Envato CodeCanyon.
Not open source. See `LICENSE.txt`.
