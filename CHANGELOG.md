# Changelog

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
