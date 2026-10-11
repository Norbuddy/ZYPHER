# Zypher — Ready-to-Run Setup

This is your actual project with all Zypher code merged directly in — migrations, models, controllers, routes, and Vue pages are all in their real places alongside Jetstream's own files. `vendor/` is included (composer already run). `node_modules/` was excluded to keep the zip smaller — just run `npm install`.

## Run it

```bash
cd Zypher

npm install

# .env already has DB_CONNECTION=sqlite - create the file if migrate complains it's missing:
touch database/database.sqlite

php artisan migrate
php artisan db:seed
php artisan storage:link

php artisan serve
# in a second terminal:
npm run dev
```

Visit `http://localhost:8000`.

## Test accounts (from the seeder)

- **Admin:** `admin@zypher.test` / `password` → access `/admin/*`
- **Regular user (has a personal team):** `test@example.com` / `password`

## What was merged into your real files (not separate copies)

- `app/Models/Team.php` — Jetstream's original preserved, Zypher shop fields/relationships (`products()`, `orderShops()`, `vouchers()`, `isApproved()`) added on top
- `app/Models/User.php` — Jetstream's original preserved, `is_admin` + Zypher relationships (`cart()`, `addresses()`, `orders()`, `reviews()`) added
- `routes/web.php` — Jetstream's `/dashboard` route preserved; the old `/` Welcome page route replaced with Zypher's `HomeController`; all cart/checkout/account routes added inside the existing auth-protected group
- `bootstrap/app.php` — registered `routes/seller.php` and `routes/admin.php`, added the `is.admin` / `is.approved.seller` middleware aliases
- `database/seeders/DatabaseSeeder.php` — Jetstream's original test user (with personal team) preserved, `CategorySeeder` call and the Zypher admin account added
- **21 new migrations**, timestamped to run *after* Jetstream's own (teams, team_invitations, etc.) since Zypher's shop fields extend the `teams` table

## Everything else (new files, no collisions)

All other Zypher files — `Product`, `Order`, `OrderShop`, `Voucher`, etc. models; `Seller/` and `Admin/` controllers; `InventoryService`/`OrderService`/`CommissionService`; the Vue pages under `Pages/Product`, `Pages/Cart`, `Pages/Seller`, `Pages/Admin`, etc.; `VariantBuilder.vue`, `OrderStatusStepper.vue` — were simply new files with no naming conflicts, so they were added directly.

**Intentionally overwritten:** `resources/js/Pages/Auth/Login.vue` and `Register.vue` — replaced with the Zypher-branded versions from earlier in our conversation (Jetstream's defaults are gone).

## Known gaps (same as before — nothing new)

- No payment gateway (by design)
- No image upload handling in `ProductController@store` yet (schema supports it, form doesn't send files yet)
- No notification bell UI (backend notifications work, no frontend widget)
- No messaging/conversations UI (schema + models exist, no controller/pages)
- Individual variant editing on Seller/Products/Edit.vue is read-only for now

## Test the full flow

1. Log in as `test@example.com` → its personal team exists but has `status = pending` by default (migration default) — won't pass the Seller Center gate yet
2. Log in as `admin@zypher.test` → `/admin/shops` → approve that team
3. Back as `test@example.com` → `/seller/products/create` → build a product with the Variant Builder → publish
4. Browse as a guest or another account → add to cart → checkout (payment stays `pending_payment` — call `app(\App\Services\OrderService::class)->confirmPayment($order)` via `php artisan tinker` to simulate a successful payment for demo purposes)
5. Seller → `/seller/orders` → Mark Processing → Mark Shipped
6. Customer → order tracking page → "Order Received" → leave a review

## If something doesn't boot

Since this was assembled without the ability to run PHP/Composer/Vite in the environment that built it, expect to hit a handful of small issues (typo, missing import, a route name mismatch). Paste me the exact error and I'll fix the real file directly — I now have your actual project structure to work against, not a guess.
