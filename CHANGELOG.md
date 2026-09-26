# Changelog

## 2.0.1

- **Dashboard & sidebar redesign**: vampire-blood theme across the app
  (Tailwind `primary` palette, Gloock + Instrument Sans). Sidebar is now the
  blood panel from the sign-in page with icons; the dashboard shows today's
  revenue as an hourly line with a vs-yesterday comparison, recent sales and
  a running-low list. Assets rebuilt in `public/build`.
- **Settings** (Admin > Settings, admins only): shop name, phone, email,
  address, currency, default tax rate, timezone, receipt footer, and SMTP
  email details with a "send test email" button. Saved values override
  `.env` at runtime; the SMTP password is stored encrypted. Receipts now show
  the shop address, contact details, currency and footer. Run
  `php artisan migrate` after updating (adds the `settings` table).
- **Forgot password**: staff can reset their password by email
  (`/forgot-password` → emailed link → `/reset-password`). Links expire after
  60 minutes; deactivated accounts can't reset; the request form never reveals
  whether an email exists. Needs working `MAIL_*` settings in `.env`.
- **Login redesign**: new vampire-blood auth screens sharing one layout
  (`components/layouts/auth.blade.php`).
- **Branding**: product renamed to **ShopPulse POS** (default `APP_NAME`,
  mail from-name, session/cache/DB fallback names, `composer.json` and
  `package-lock.json` package names, README and documentation titles).

## 2.0.0

**Converted from a restaurant/cafe POS into a generic retail POS.** The
restaurant-specific differentiators (dine-in floor/table management, the
Kitchen Display System, per-item recipe/ingredient stock, modifiers/variants,
and QR-code table self-ordering) are gone; the app is now aimed at any small
retail business.

- **Data model**: `MenuItem` → `Product` (adds `sku`, and per-product
  `track_stock`/`stock_quantity`/`low_stock_threshold` — stock is now
  tracked directly on the product instead of via a separate ingredient/
  recipe system); `Order` → `Sale`; `OrderItem` → `SaleItem`; `Bill` now
  belongs to a `Sale` instead of an `Order`. `Category` is unchanged (it was
  already generic)
- **Removed entirely**: `floors`/`tables` (dining-table management),
  `item_variants`, `modifier_groups`/`modifiers`, `ingredients` +
  the ingredient/recipe pivot, the Kitchen Display System
  (`App\Http\Controllers\KDS\TicketController`, `App\Events\
  OrderItemStatusUpdated`, station-filtered tickets, Echo/Pusher
  broadcasting), and QR-code customer self-ordering
  (`PublicOrderController`, the public no-login menu, `Admin\
  TableController@qr`) — all of these were dine-in/restaurant-specific
- **Stock**: `StockService` now deducts a product's stock the moment it's
  added to a sale (not on a "served" kitchen-status transition, since there
  is no longer a kitchen workflow), and restores it if the line item is
  removed from a still-open sale
- **Sale lifecycle simplified**: `open → billed → closed` (drops the
  restaurant-only `sent`/`served` kitchen-prep stages); a sale item is a
  flat product/quantity/notes line with no per-item status, variant, or
  modifier selection
- **Roles simplified**: `waiter` and `kitchen` are gone; the fixed role set
  is now `admin`, `manager`, `cashier`
- **Routes/nav**: `admin.menu-items.*` → `admin.products.*`;
  `pos.orders.*` → `pos.sales.*`; the `kds.*` and public `order.*` route
  groups, and the admin Floors/Tables/Modifier Groups screens, are removed;
  the sidebar and dashboard were updated to match
- Branding: default `APP_NAME`/`DB_DATABASE`/`composer.json` name and
  description are now generic ("POS" / `pos`) instead of restaurant-themed;
  the red/charcoal/white visual theme itself is unchanged
- Test suite updated to match: `OrderFlowTest` → `SaleFlowTest`,
  `OrdersIndexTest` → `SalesIndexTest`, `PublicOrderingTest` removed,
  `RoleEnumTest`/`RoleAccessTest`/others updated for the new role set
- This conversion has only been checked statically (`php -l`, manual
  route/view/foreign-key cross-checking) — see the README's "Still open"
  section for what to verify once `composer install` can actually run

## 1.2.0

**Employee management & attendance.** The `shifts` table
(`opening_till`/`closing_till`, for cash-drawer reconciliation) already
existed in the original schema and model layer but had zero controller/
route/view wired up to it - this release builds that missing layer, plus
turns the bare staff add/remove list into a full employee profile screen.

- **Self-service clock-in/clock-out**: a header widget (any authenticated
  role) records the opening till amount at clock-in and the closing till
  amount at clock-out; rejects a double clock-in or a clock-out with no
  active shift
- **Full employee profile screen**: name/email/phone/branch/role/active-
  status editing (was previously role + active-status only), reachable
  from the staff list, with that employee's own shift history alongside it
- **Admin attendance report**: every employee's shift history in one
  place, filterable by employee or to just who's currently clocked in -
  admin|manager only, same as the rest of `Admin\*`
- New tests: clock-in/out success and error paths, full employee-profile
  editing (including email-uniqueness-ignoring-self and deactivating an
  account), and the attendance report's role gating and filters

## 1.1.0

Focused on CodeCanyon submission-readiness: a standout feature, and a real
security pass rather than more visual polish.

- **QR-code customer self-ordering** (differentiator feature) - each table
  gets a random, non-guessable token; scanning its printed QR code opens a
  no-login menu where a customer can build a cart and submit it. It lands
  as pending items on that table's order exactly as if a waiter had typed
  them in, so KDS/billing/reporting needed zero changes to support it.
  `Admin\TableController@qr` renders a printable QR code client-side (the
  `qrcode` npm package - no new Composer dependency, so shared-hosting
  installs aren't affected)
- **Self-service profile/password page**, styled as a WordPress-style
  settings screen (label+description rows, side-by-side sections) with a
  header avatar dropdown
- **Orders list rebuilt as a list-table**: status tabs with live counts,
  search by order #/table/type, zebra-striped rows, hover row-actions
- **Security hardening**:
  - Login is now rate-limited (5 attempts/minute per email+IP) - there was
    previously no brute-force protection on `/login` at all
  - Fixed the installer's `.env` writer: it used to hand a raw DB password
    straight to `preg_replace()`, which treats `$1`-style substrings in the
    *replacement* as backreferences (silently mangling such a password) and
    didn't quote/escape values, so a password containing a space, `#`, or
    quote could corrupt or truncate `.env`. Now quotes/escapes every value
    and uses `preg_replace_callback()`
  - `.env.example` now defaults to `APP_ENV=production` / `APP_DEBUG=false`
    (was `local`/`true` - a real risk if a buyer never changes it before
    going live, since debug mode leaks stack traces publicly)
  - Documented what was already solid (explicit `$fillable` everywhere, no
    raw-input mass assignment, Blade's default escaping used everywhere,
    CSRF on by default, a live Envato Author-API purchase-code check) in
    the README's new Security section, alongside what's still open
- Neutral palette swapped from Tailwind `gray` to `zinc` for a truer
  charcoal, and the status-badge palette consolidated from five competing
  hues down to four purposeful ones (neutral/in-progress/success/urgent)
- New tests: QR ordering (menu access by token, cart submission, re-using
  an open tab), the login rate limiter, the `.env`-writing helper's
  quoting/escaping, profile/password self-service, and the orders list's
  filtering

## 1.0.0

Initial build.

- Full Laravel 12 application: menu & floor management, order taking,
  Kitchen Display System, billing (tax/discount/service charge, split
  billing, multi-payment), sales and low-stock reports, an admin
  dashboard, and a five-role permission system (admin, manager, cashier,
  waiter, kitchen)
- Web installer wizard: requirements check, Envato purchase-code
  verification, database setup, done
- Red/white brand theme applied consistently across every screen
  (`tailwind.config.js`'s `primary` color, shared component classes in
  `resources/css/app.css`, a themed pagination view)
- PHPUnit feature/unit test suite covering login, role-gated access,
  the full order-to-payment lifecycle, billing math, and the roles/admin
  seeder
- Verified end-to-end against a live cPanel/MySQL deployment; notable
  fixes that came out of that pass:
  - Added the spatie/laravel-permission migration (roles/permissions
    tables), previously only created via a manual `vendor:publish` step
  - Fixed `role:admin,manager`-style middleware, which Laravel was
    silently misparsing as a role plus an auth guard name instead of two
    roles (must be pipe-separated: `role:admin|manager`)
  - Granted the `cashier` role actual route access - it previously had
    none, despite being one of the five roles the app defines
  - Moved the shared admin layout into `resources/views/components/layouts/`
    so Blade's `<x-layouts.admin>` tag can actually find it
  - Added the missing `config/view.php` and `config/hashing.php` (the
    hand-built framework skeleton never generated them, causing a blade
    compiler crash and relying on hashing's implicit default)
  - Committed compiled Vite output (`public/build/`), since most shared
    hosts have no Node.js to run `npm run build` on the server
  - Added a root `.htaccess` for hosts whose Document Root can't be
    pointed at `public/`
