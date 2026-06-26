# Phase 1 — ERP Foundation

Phase 1 establishes the production-ready foundation for the E-Commerce ERP system. No storefront or full CRUD modules yet — those arrive in Phases 2–5.

## Stack

- Laravel 12+
- PHP 8.3+
- MySQL 8+
- Tailwind CSS + Alpine.js (via Laravel Breeze)
- Spatie Laravel Permission
- Laravel Sanctum
- kalnoy/nestedset (category trees)

## What's Included

### Database (55+ tables)

Migrations cover users, RBAC, catalog, products, commerce, orders, payments, CMS, and system tables.

### Architecture

| Layer | Location |
|-------|----------|
| Enums | `app/Enums/` |
| Models | `app/Models/` (42 models) |
| Repository contracts | `app/Repositories/Contracts/` |
| Eloquent repositories | `app/Repositories/Eloquent/` |
| Services | `app/Services/` |
| Policies | `app/Policies/` |
| Form requests | `app/Http/Requests/Admin/` |
| Middleware | `app/Http/Middleware/EnsureUserIsStaff.php` |

### Authentication & RBAC

- Laravel Breeze (Blade stack)
- Roles: `super-admin`, `admin`, `manager`, `inventory-staff`, `sales-staff`, `content-manager`, `customer`
- Granular permissions for all 20 ERP modules (used in later phases)

### Admin Shell

- Route: `/admin` (staff only)
- Dashboard with placeholder stats
- Sidebar navigation scaffold for upcoming modules

### Seeders

| Seeder | Purpose |
|--------|---------|
| `RolesAndPermissionsSeeder` | Roles + permissions |
| `SiteSettingsSeeder` | Default shop settings |
| `AdminUserSeeder` | Super admin account |

**Default admin:** `admin@sanitarystore.pk` / `password`

## Setup

```powershell
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Verify installation:

```powershell
php artisan erp:verify-phase1
php artisan test --filter=AdminDashboardTest
```

## Phase Roadmap

| Phase | Modules |
|-------|---------|
| **2** | Product Management, Categories, Brands, Inventory |
| **3** | Orders, Customers, Billing & Invoices, Shipping |
| **4** | Storefront, Checkout & Payments, Coupons |
| **5** | Reviews, Reports, Settings, Notifications, Customer Frontend |

## Key Routes

| Route | Description |
|-------|-------------|
| `/` | Welcome page |
| `/login` | Breeze login |
| `/dashboard` | Customer dashboard (staff redirected to `/admin`) |
| `/admin` | ERP admin dashboard |
| `/media/placeholder` | SVG placeholder for product images |

## Configuration

Shop settings: `config/shop.php` and `.env` keys prefixed with `SHOP_`.
