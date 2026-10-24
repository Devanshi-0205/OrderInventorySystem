# Order & Inventory Management System

A full-stack Order & Inventory Management System built with **Vue 3** (frontend) and **Laravel 13** (REST API backend).

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Vue 3.5, Vite 8, Vue Router 4, Pinia, Axios, PrimeVue 4 (Aura theme) |
| Backend | Laravel 13, PHP 8.4 or above (required by Pest 5 / PHPUnit 13), Laravel Sanctum 4 (token auth) |
| Database | MySQL |
| Testing | **PestPHP v5.3** + `pest-plugin-laravel` 5.0 on PHPUnit 13 (backend), Vitest (frontend) |
| Linting | ESLint + eslint-plugin-vue (frontend), Laravel Pint (backend) |
| Node.js | ≥ 22.18.0 required — use `nvm use 22.23.4` (nvm-windows) |

## Project Structure

```
order-inventory-system/
├── backend/                  # Laravel REST API
│   ├── app/
│   │   ├── Exceptions/       # InsufficientStockException
│   │   ├── Http/
│   │   │   ├── Controllers/  # Auth, Dashboard, Product, Order
│   │   │   ├── Middleware/   # CheckRole
│   │   │   └── Requests/     # Form Request validators
│   │   └── Models/           # User, Product, Order, OrderItem
│   ├── database/
│   │   ├── migrations/       # users + role, products, orders, order_items
│   │   └── seeders/          # UserSeeder, ProductSeeder
│   └── routes/api.php        # All API routes
├── frontend/                 # Vue 3 SPA
│   └── src/
│       ├── api/              # Axios instance + per-resource service files
│       ├── components/       # AppLayout (sidebar + topbar)
│       ├── pages/            # 8 route-level pages
│       ├── stores/           # Pinia stores (auth, products, orders)
│       └── router/           # Vue Router with nav guards
├── .cursor/skills/           # Cursor Agent Skills
└── README.md
```

## Prerequisites

| Software | Version | Notes |
|---|---|---|
| PHP | 8.4 or above | Required by Pest 5 / PHPUnit 13. Extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`. Tests also need `pdo_sqlite` |
| Composer | 2.x | PHP dependency manager |
| MySQL | 5.7+ / 8.x (or MariaDB 10.3+) | Provided by WAMP/XAMPP, or install standalone |
| Node.js | ≥ 22.18.0 (or ≥ 24.12.0) | nvm-windows: `nvm install 22 && nvm use 22.23.4` |
| npm | 10+ (bundled with Node) | Frontend dependency manager |
| Git | any recent version | |

> On Windows/WAMP, make sure the PHP version used by your **command line** (`php -v`) is 8.4+, not just the one selected in WAMP.

## Quick Start

### 1. Clone

```bash
git clone https://github.com/Devanshi-0205/OrderInventorySystem.git
cd OrderInventorySystem
```

### 2. Create the database

Start MySQL (e.g. start WAMP/XAMPP), then create an empty database:

```bash
mysql -u root -p -e "CREATE DATABASE order_inventory CHARACTER SET utf8 COLLATE utf8_unicode_ci;"
```

(Or create a database named `order_inventory` from phpMyAdmin.) On a default WAMP install the `root` user has an empty password.

### 3. Backend setup

```bash
cd backend
composer install
```

Copy the environment file:

```bash
# Windows (cmd / PowerShell)
copy .env.example .env
# macOS / Linux
cp .env.example .env
```

Open `backend/.env` and **uncomment / edit** the database block to match your local MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_inventory
DB_USERNAME=root
DB_PASSWORD=          # your local MySQL password 
DB_CHARSET=utf8
DB_COLLATION=utf8_unicode_ci
```

Generate the app key, create the tables and load demo data:

```bash
php artisan key:generate
php artisan migrate --seed
```

This runs all migrations (`users` + role, `products`, `orders`, `order_items`, Sanctum tokens, session/cache/queue tables) and the `UserSeeder` / `ProductSeeder`. To wipe and rebuild at any time (local only): `php artisan migrate:fresh --seed`.

Start the API server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

API base URL: `http://127.0.0.1:8000/api`

### 4. Frontend setup

Open a **new terminal** (keep the backend running):

```bash
cd frontend
nvm use 22.23.4      # skip if Node >= 22.18 is already active
npm install
```

Copy the environment file (pre-configured to talk to the backend above):

```bash
# Windows (cmd / PowerShell)
copy .env.example .env
# macOS / Linux
cp .env.example .env
```

`frontend/.env` contains:

```dotenv
VITE_APP_NAME="Order Inventory System"
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Change `VITE_API_BASE_URL` only if you run the backend on a different host/port.

Start the dev server:

```bash
npm run dev
```

Frontend URL: `http://localhost:5173` 

Log in with one of the [demo credentials](#demo-credentials) below.

### 5. Run the tests

```bash
# Backend (Pest)
cd backend
composer test

# Frontend (Vitest)
cd frontend
npm run test:unit -- --run
```

See [Development Commands](#development-commands) for more options.

### Troubleshooting

| Problem | Fix |
|---|---|
| `Access denied for user` / `Unknown database` on migrate | Check `DB_*` values in `backend/.env` and that the `order_inventory` database exists |
| `Your PHP version does not satisfy...` on `composer install` | Use PHP 8.4+ on the command line (`php -v`) |
| `No application encryption key has been generated` | Run `php artisan key:generate` |
| Frontend shows network errors / 401 on login | Confirm the backend is running on port 8000 and `VITE_API_BASE_URL` matches |
| `Specified key was too long` during migrate | Keep `DB_CHARSET=utf8` / `DB_COLLATION=utf8_unicode_ci` as shown above |

## Demo Credentials

These are **local seed-data accounts only** (created by `UserSeeder`), not real credentials. Change or remove them before deploying anywhere public.

| Role  | Email | Password |
|---|---|---|
| Admin | admin@example.com | password |
| Staff | staff@example.com | password |

## API Reference

All endpoints are prefixed with `/api`. Protected routes require `Authorization: Bearer <token>`.

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| POST | `/login` | No | — | Authenticate, returns token |
| POST | `/logout` | Yes | any | Revoke token |
| GET | `/user` | Yes | any | Authenticated user profile |
| GET | `/dashboard` | Yes | any | Summary stats |
| GET | `/products` | Yes | any | List products (search + filter) |
| POST | `/products` | Yes | admin | Create product |
| GET | `/products/{id}` | Yes | any | Product detail |
| PUT | `/products/{id}` | Yes | admin | Update product (full replace) |
| DELETE | `/products/{id}` | Yes | admin | Delete product |
| GET | `/orders` | Yes | any | List orders (admins see all) |
| POST | `/orders` | Yes | any | Place order (stock deducted atomically) |
| GET | `/orders/{id}` | Yes | any | Order detail |
| PATCH | `/orders/{id}/status` | Yes | admin | Update order status |

## Available Application Screens

| URL | Description | Auth |
|---|---|---|
| `/login` | Login page | Public |
| `/` | Dashboard — summary stats | Any |
| `/products` | Product list with search & stock filter | Any |
| `/products/new` | Create product form | Admin |
| `/products/:id` | Product detail with Edit/Delete actions | Any (actions: Admin) |
| `/products/:id/edit` | Edit existing product form | Admin |
| `/orders` | Order list with status filter | Any |
| `/orders/new` | Create order — select products + quantities | Any |
| `/orders/:id` | Order detail + admin status update | Any |

## Development Commands

### Backend

```bash
cd backend

# Run the full PEST v5 test suite (67 tests, 199 assertions)

php -d zend.assertions=-1 vendor/bin/pest

# Equivalent via composer script 
composer test

# Run a specific test file
php -d zend.assertions=-1 vendor/bin/pest tests/Feature/OrderTest.php

# Run tests matching a description keyword
php -d zend.assertions=-1 vendor/bin/pest --filter "insufficient stock"

php artisan migrate:fresh --seed   # reset DB + reseed (local only)
./vendor/bin/pint                  # format PHP (Laravel Pint)
```

#### Backend tests (PestPHP v5)

Tests live in `backend/tests/Feature/`
and in `backend/tests/Pest.php`.

| Test file | Business rules covered |
|---|---|
| `ProductTest.php` | Product validation (required fields, negative price/stock, SKU length, duplicate SKU → 422), CRUD, stock-status label |
| `OrderTest.php` | Valid stock → 201 + atomic deduction; backend-computed total; unit_price snapshot; insufficient stock → 409 + unchanged stock; multi-item rollback; concurrent over-order prevention; input validation |
| `AuthorizationTest.php` | Unauthenticated → 401 (parametrized across all routes); staff on admin routes → 403 with no side-effects; admin allowed everywhere; staff see only own orders; Sanctum Bearer-token login → use → logout |

> **Concurrency note:** A single PHP test process cannot issue truly parallel HTTP requests, and SQLite
> ignores `SELECT … FOR UPDATE`. The concurrency tests in `OrderTest.php` verify the invariants that
> `DB::transaction()` + `lockForUpdate()` guarantee — no overselling, no negative stock — by firing
> competing requests sequentially. The lock path is exercised end-to-end; true concurrent stress
> tests should be run against MySQL in a dedicated environment.

### Frontend

```bash
cd frontend
npm run dev           # start Vite dev server
npm run build         # production build
npm run lint:check    # ESLint check
npm run lint          # ESLint auto-fix
npm run test:unit     # Vitest tests
npm run test:coverage # Vitest with coverage report
```

## Architecture Notes

- **Token authentication:** Sanctum issues a Bearer token on login. The frontend stores it in `localStorage` and sends it as `Authorization: Bearer <token>`.
- **Inventory safety:** Order creation runs inside a `DB::transaction()` with `lockForUpdate()` on each product row to prevent overselling under concurrent requests.
- **Backend-authoritative pricing:** The frontend never sends prices or order totals. The backend always calculates totals from current database prices.
- **Role enforcement:** The `CheckRole` middleware enforces `admin` / `staff` permissions on every relevant route. Frontend route guards are UX-only.
- **String length constraint:** `AppServiceProvider` sets `Builder::defaultStringLength(191)` and `.env` uses `utf8` charset to work with older MySQL configurations.

## AI Tools Used

### Which AI coding tool(s) did I use?

- **ChatGPT** — for prompt engineering: turning each feature idea into a detailed, compact brief.
- **Cursor** (AI coding assistant in the IDE) — for generating and modifying the actual code, guided by project-specific skill files.

### Which tasks did I use them for?

| Task | Tool |
|---|---|
| Writing a detailed but token-efficient brief for each feature (scope, business rules, API shape, edge cases) | ChatGPT |
| Writing the Frontend and Backend "skill" Markdown files that define stack, versions and coding standards | ChatGPT / Cursor |
| Generating feature code (Laravel controllers, form requests, migrations, Vue pages, Pinia stores) | Cursor |
| Generating Pest tests (validation, authorization, stock deduction, rollback, concurrency) | Cursor |
| Diagnosing failing tests and generating targeted bug fixes | Cursor |
| Drafting documentation | Cursor |

### My workflow

1. **Prompt engineering first (ChatGPT).** Before writing any code I explained the feature I wanted to build to ChatGPT and had it help me shape a *brief but detailed* prompt. A precise prompt meant the coding assistant needed fewer clarifying rounds and fewer retries, which kept token usage to a minimum and produced a stable, working feature on the first or second pass instead of many iterations.
2. **Skill files to keep standards intact.** Beforehand I created a skill Markdown file for each side of the app — [`.cursor/skills/laravel-backend/SKILL.md`](.cursor/skills/laravel-backend/SKILL.md) and [`.cursor/skills/vue-frontend/SKILL.md`](.cursor/skills/vue-frontend/SKILL.md). They record the framework versions, conventions, folder structure and rules (e.g. Form Requests for validation, role middleware, backend-authoritative pricing, Pinia + service-file pattern on the frontend). Every time code was generated, the assistant followed these files, so the output stayed consistent with the project's coding standards and library versions rather than drifting into generic or outdated patterns.
3. **Tests as the safety net.** Unit/feature tests encode the business rules, so they kept validation in check. When a test failed, or when a bug or new requirement came up, I fed the failing test output back to the assistant and had it generate a fix or the new development against that spec — the tests then confirmed the fix and guarded against regressions.

### Representative prompts

> **1 (ChatGPT – prompt engineering):** "I'm building an Order & Inventory system with a Laravel API and Vue 3 frontend. Help me write a short, precise brief for the 'create order' feature: user picks products + quantities, backend validates stock, deducts it atomically, calculates totals server-side, and rejects the whole order if any item is short. Include roles, validation rules, response codes and edge cases. Keep it as compact as possible."

> **2 (Cursor – feature build, using the skill files):** "Following the laravel-backend skill, implement `POST /api/orders` per this brief: Form Request validation, `DB::transaction()` with `lockForUpdate()`, unit-price snapshot on `order_items`, 409 on insufficient stock with no partial changes. Add Pest tests for each rule."

> **3 (Cursor – test-driven fix):** "This Pest test is failing: <pasted failure output>. Using the existing validation/authorization rules and the backend skill, find the root cause and fix the code, not the test, unless the test itself is wrong."

### What the AI generated

- Laravel scaffolding: migrations, Eloquent models and factories, Form Request validators, API controllers, `CheckRole` middleware, seeders.
- Order placement logic (transactional stock deduction, backend-computed totals, price snapshots, custom `InsufficientStockException`).
- Vue 3 pages, Pinia stores, Axios service files and route guards, styled with PrimeVue.
- The Pest feature test suite (products, orders, rollback, concurrency invariants, authorization).
- Initial drafts of the skill files, prompts and this documentation.

### What I modified, rejected or improved, and why

- **Kept standards under my control:** I wrote and refined the skill files myself so generated code matched my chosen stack and versions; output that deviated from them was corrected or regenerated instead of accepted.
- **Business rules stay server-side:** Prices and order totals are never trusted from the client, and stock checks happen inside a transaction with row locking. The frontend route guards are treated as UX only — real enforcement is in backend middleware.
- **Fixes driven by evidence, not guesses:** When something broke, I used the failing test as the spec for the fix rather than accepting a speculative rewrite.
- **Environment-specific adjustments:** I adapted the generated setup to my environment (e.g. the PHP 8.4 / PHPUnit 13 `zend.assertions` workaround in `composer test`, the MySQL `utf8` charset and 191 string-length setting).
- **Secrets kept out of the repo:** Only `.env.example` files are tracked; `.env` is git-ignored and the seeded accounts are demo-only.

### How I verified the code was correct and secure

- **Automated tests:** The Pest suite (see [Backend tests](#backend-tests-pestphp-v5)) covers validation, CRUD, atomic stock deduction, insufficient-stock rejection with rollback, over-order prevention, role-based authorization (401/403), per-user order visibility and the Sanctum token lifecycle. Frontend checks run through Vitest and ESLint.
- **Security review of generated code:** I checked that every protected route sits behind `auth:sanctum` and, where needed, the `CheckRole` middleware; that all input goes through Form Requests; that Eloquent/query-builder bindings are used (no raw SQL concatenation); and that no credentials or tokens are hard-coded.
- **Manual end-to-end checks:** I ran the app locally and exercised the flows as both Admin and Staff (login, product CRUD, placing orders, status updates, forbidden actions) to confirm the UI and API behave as the tests describe.
- **Understanding over copy-paste:** I reviewed each generated change before keeping it and can explain the implementation — in particular the transaction/locking strategy, the role model and the token flow.
