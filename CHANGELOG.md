# Changelog

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
