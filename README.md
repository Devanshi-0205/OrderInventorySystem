# Order & Inventory Management System

A full-stack Order & Inventory Management System built with **Vue 3** (frontend) and **Laravel 13** (REST API backend).

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Vue 3.5, Vite 8, Vue Router 4, Pinia, Axios, PrimeVue 4 (Aura theme) |
| Backend | Laravel 13, PHP 8.4 or above (required by Pest 5 / PHPUnit 13), Laravel Sanctum 4 (token auth) |
| Database | MySQL (via WAMP Server) |
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

- **WAMP/XAMPP Server** running (MySQL + Apache)
- **PHP 8.4 or above** and **Composer 2.x**
- **Node.js ≥ 22.18.0** — use nvm-windows: `nvm install 22 && nvm use 22.23.4`
- **Git**

## Quick Start

### 1. Clone

```bash
git clone https://github.com/Devanshi-0205/OrderInventorySystem.git
cd order-inventory-system
```

### 2. Backend setup

```bash
cd backend
composer install
```

Copy and configure environment:

```bash
copy .env.example .env
```
also uncomment the Database setup attributes.

```bash
php artisan key:generate
php artisan migrate --seed
```

Start the API server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

API base URL: `http://127.0.0.1:8000/api`

### 3. Frontend setup

Open a **new terminal**:

```bash
cd frontend
nvm use 22.23.4  
npm install
```

Copy environment file (already pre-configured):

```bash
copy .env.example .env
```

Start the dev server:

```bash
npm run dev
```

Frontend URL: `http://localhost:5174`

## Demo Credentials

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

# Run the full PestPHP v5 suite (66 tests, 197 assertions)
./vendor/bin/pest

# Equivalent via composer script
composer test

# Run a specific test file
./vendor/bin/pest tests/Feature/OrderTest.php

# Run tests matching a description keyword
./vendor/bin/pest --filter "insufficient stock"

php artisan migrate:fresh --seed   # reset DB + reseed (local only)
./vendor/bin/pint                  # format PHP (Laravel Pint)
```

#### Backend tests (PestPHP v5)

Tests live in `backend/tests/Feature/`, run against an in-memory SQLite database (see `phpunit.xml`)
and use `RefreshDatabase`, so your MySQL data is never touched. Shared helpers (`adminUser()`,
`staffUser()`) and the `TestCase` / `RefreshDatabase` setup are in `backend/tests/Pest.php`.
Model factories exist for `User`, `Product`, `Order` and `OrderItem`.

| Test file | Business rules covered |
|---|---|
| `ProductTest.php` | Product validation (required fields, negative price/stock, SKU length, duplicate SKU → 422), CRUD |
| `OrderTest.php` | Order with valid stock → 201 and stock deducted; insufficient stock → 409 and stock unchanged; multi-item rollback |
| `OrderRollbackTest.php` | Client price/total ignored; stock restored if the DB write fails after deduction; no stack-trace leak; quantity validation |
| `InventoryConcurrencyTest.php` | Stock never goes negative when many users compete for scarce stock; duplicate lines in one order; lock/transaction path |
| `AuthorizationTest.php` | Unauthenticated → 401; staff on admin routes → 403 (no side effects); staff only see own orders; Sanctum login → token → logout |

> **Concurrency note:** a single PHP test process cannot issue truly parallel HTTP requests, and SQLite
> ignores `SELECT … FOR UPDATE`. The concurrency tests therefore verify the invariants the row lock
> guarantees (no overselling, no negative stock, no lost updates) by firing competing requests
> back-to-back. The `FOR UPDATE` clause itself is asserted only when tests run on MySQL.
>
> **PHP version:** Pest 5 requires PHP ^8.4 and PHPUnit 13 requires ≥ 8.4.1.

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
