---
name: laravel-backend
description: Use when creating, modifying, reviewing, debugging, or testing the Laravel backend of the Order Inventory Management System, including REST APIs, validation, database migrations, Eloquent models, transactions, inventory management, authentication, authorization, and automated tests.
paths:
  - "backend/**"
---

# Laravel Backend Skill — Order Inventory System

## Stack
- **Laravel 13.17** · **PHP 8.3** · **MySQL** (utf8 charset, 1000-byte key limit)
- **Laravel Sanctum 4** — token-based auth (`Authorization: Bearer <token>`)
- **PHPUnit 12.5** · **Laravel Pint** (formatter)

## Domain Schema
```
users        id · name · email · role(enum:admin,staff) · password · timestamps
products     id · name · sku(unique,≤100) · description · price(decimal10,2) · stock_quantity(uint) · timestamps
orders       id · user_id(FK) · status(enum:pending,confirmed,cancelled) · total_amount(decimal10,2) · timestamps
order_items  id · order_id(FK cascade) · product_id(FK restrict) · quantity(uint) · unit_price(decimal10,2) · timestamps
```

## API Routes (`routes/api.php`)
| Method | URI | Auth | Role |
|---|---|---|---|
| POST | `/api/login` | No | — |
| POST | `/api/logout` | Yes | any |
| GET | `/api/user` | Yes | any |
| GET | `/api/dashboard` | Yes | any |
| GET | `/api/products` | Yes | any |
| POST | `/api/products` | Yes | admin |
| GET | `/api/products/{id}` | Yes | any |
| GET | `/api/orders` | Yes | any |
| POST | `/api/orders` | Yes | any |
| GET | `/api/orders/{id}` | Yes | any |
| PATCH | `/api/orders/{id}/status` | Yes | admin |

## Hard Rules

### Architecture
- Controllers: validate (Form Request) → act (Eloquent/service) → return JSON. No business logic in controllers.
- Use `app/Http/Requests/` for all non-trivial validation.
- Use Eloquent relationships; avoid raw SQL.
- Return `{ "data": {…}, "message": "…" }` for single resources, Laravel paginator JSON for lists.
- HTTP codes: `201` Created · `401` Unauth · `403` Forbidden · `404` Not found · `409` Stock conflict · `422` Validation.

### Database migrations
- MySQL-compatible only. Indexed `string()` columns **must be ≤ 191 characters** (`AppServiceProvider` sets `defaultStringLength(191)`; charset is `utf8`).
- Money: `decimal('col', 10, 2)` — never `float`.
- Quantity/stock: `unsignedInteger`.
- Foreign keys: explicit `onDelete('cascade'|'restrict')`.
- **Never run `migrate:fresh` or `migrate:reset` without confirming the environment is local.**

### Order creation — non-negotiable inventory rules
1. **Backend calculates total.** Reject any `price` or `total_amount` from the request payload.
2. Wrap the entire operation in `DB::transaction()`.
3. Use `Product::lockForUpdate()->findOrFail()` before reading stock — prevents overselling under concurrent requests.
4. If `stock_quantity < requested_quantity` → throw `InsufficientStockException` → transaction rolls back → returns `409`.
5. Decrement stock with `$product->decrement('stock_quantity', $qty)` inside the transaction.
6. Snapshot `unit_price` from the locked product row into `OrderItem`.

### Authentication & authorization
- Sanctum token flow: `POST /api/login` → `createToken()` → return `plainTextToken`.
- Protected routes use `middleware('auth:sanctum')`.
- Role enforcement uses `CheckRole` middleware, registered as alias `'role'` in `bootstrap/app.php`.
  - Usage: `->middleware('role:admin')` or `->middleware('role:admin,staff')`.
- Staff see only their own orders; admins see all — enforced in `OrderController`, not the frontend.
- Do not invent a new auth mechanism. If Sanctum is not configured, explain the setup steps instead.

### Security
- Never expose stack traces in API responses (`APP_DEBUG=false` in production).
- Use `$fillable` on all models — no mass-assignment vulnerabilities.
- Validate all request input through Form Requests before trusting it.
- `InsufficientStockException` is mapped to `409` in `bootstrap/app.php → withExceptions()`.

## Verification — run before claiming anything works
```bash
php artisan test          # all tests must pass
./vendor/bin/pint --test  # code style check (no auto-fix in CI)
```

**Required test cases for any new order/inventory feature:**
- Valid product creation → 201; duplicate SKU → 422.
- Order creation deducts stock → 201; insufficient stock → 409, stock unchanged.
- Unauthenticated request → 401; staff on admin route → 403.

## Agent Rules
1. Read the target file before editing it.
2. Make the smallest change that satisfies the requirement.
3. Do not refactor unrelated code.
4. Do not run destructive migration commands without confirming the environment.
5. If auth or roles are not yet configured for a feature, explain required setup before writing code that depends on it.
6. Report: files changed · migration filenames · `php artisan test` output · limitations.
