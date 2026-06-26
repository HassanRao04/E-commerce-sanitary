# Sanitary Store — Laravel 12 Project Structure

Production e-commerce storefront + ERP admin panel.  
**Stack:** Laravel 12 · PHP 8.3+ · MySQL · Tailwind 3 · Vite 7 · Alpine.js · Spatie Permission · Sanctum

---

## 1. Folder Structure

```
sanitary/
├── app/
│   ├── Console/Commands/          # Artisan commands
│   ├── Contracts/                 # Service interfaces (payments)
│   ├── DataTransferObjects/       # Payment DTOs
│   ├── Enums/                     # Backed enums (OrderStatus, PaymentMethod, …)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # ERP admin panel (16 controllers)
│   │   │   ├── Auth/              # Breeze auth (9 controllers)
│   │   │   └── Storefront/        # Public shop (11 controllers)
│   │   ├── Middleware/            # Custom middleware
│   │   └── Requests/
│   │       ├── Admin/             # Admin form validation
│   │       ├── Auth/
│   │       └── Storefront/
│   ├── Models/                    # Eloquent models + Concerns/
│   ├── Policies/                  # Authorization policies
│   ├── Providers/                 # AppServiceProvider (bindings + gates)
│   ├── Repositories/
│   │   ├── Contracts/             # Repository interfaces
│   │   └── Eloquent/              # Eloquent implementations
│   ├── Services/
│   │   ├── Admin/                 # Admin domain services
│   │   └── Payments/              # Payment gateway adapters
│   ├── Support/Exports/           # CSV/XLSX/PDF helpers
│   └── View/Components/           # Blade layout components
├── bootstrap/
│   ├── app.php                    # Laravel 12 app bootstrap (routes, middleware)
│   ├── providers.php
│   └── cache/
├── config/
│   ├── admin.php                  # Admin menu, sidebar, permissions map
│   ├── payments.php               # Gateway credentials
│   ├── permission.php             # Spatie roles/permissions
│   ├── reports.php                # Report type registry
│   └── shop.php                   # Storefront settings (currency, tax, shipping)
├── database/
│   ├── factories/
│   ├── migrations/                # 21 migrations
│   └── seeders/                   # 14 seeders
├── public/
├── resources/
│   ├── css/
│   │   ├── app.css                # Tailwind entry
│   │   └── admin-shell.css        # Admin sidebar/layout
│   ├── js/
│   │   ├── app.js                 # Vite entry (Alpine)
│   │   ├── bootstrap.js           # Axios
│   │   └── admin/                 # Admin Alpine modules
│   └── views/
│       ├── admin/                 # ERP Blade views
│       ├── auth/
│       ├── components/            # Reusable Blade components
│       ├── layouts/               # admin, storefront, guest, app
│       ├── profile/
│       └── storefront/            # Shop Blade views
├── routes/
│   ├── web.php                    # Root web routes
│   ├── storefront.php             # Shop routes (shop.*)
│   ├── admin.php                  # Admin routes (admin.*)
│   ├── auth.php                   # Breeze auth
│   └── console.php
├── storage/
└── tests/
    ├── Feature/
    │   ├── Admin/
    │   ├── Auth/
    │   └── Storefront/
    └── Unit/
```

---

## 2. Routes

### Entry (`routes/web.php`)

| Method | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/media/placeholder` | `media.placeholder` | web |
| GET | `/dashboard` | `dashboard` | auth, verified |
| GET/PATCH/DELETE | `/profile` | `profile.*` | auth |

Includes: `storefront.php`, `admin.php`, `auth.php`

### Storefront (`routes/storefront.php`) — prefix name `shop.*`

| Group | Routes |
|-------|--------|
| Catalog | `/`, `/shop`, `/products`, `/products/{slug}`, `/categories/{slug}` |
| CMS | `/about`, `/contact` |
| Tracking | `/track-order` |
| Wishlist | `/wishlist` (index, add, remove) |
| Account | `/account/*` (auth) — dashboard, orders |
| Cart | `/cart`, `/cart/items`, `/cart/coupon` |
| Checkout | `/checkout`, POST place order, success |
| Payment | `/payment/{order}` |

### Admin (`routes/admin.php`) — prefix `/admin`, name `admin.*`, middleware `auth`, `staff`

| Section | Resources |
|---------|-----------|
| Dashboard | `/` |
| People | users, customers |
| Catalog | products, categories, brands, inventory |
| Commerce | orders, invoices, payments, coupons, shipping |
| Engagement | reviews |
| Insights | reports (+ export csv/excel/pdf) |
| System | settings |

All admin routes gated with Spatie `can:` permissions (`products.view`, `orders.update`, etc.)

### Auth (`routes/auth.php`) — Breeze

Guest: register, login, forgot/reset password  
Auth: verify email, confirm password, update password, logout

### Bootstrap (`bootstrap/app.php`)

```php
->withRouting(web: __DIR__.'/../routes/web.php', commands: ..., health: '/up')
->withMiddleware(fn (Middleware $m) => $m->alias(['staff' => EnsureUserIsStaff::class]))
```

---

## 3. Controllers

### Admin (`app/Http/Controllers/Admin/`)

| Controller | Responsibility |
|------------|----------------|
| `DashboardController` | KPIs, charts, recent activity |
| `ProductController` | Product CRUD, images |
| `CategoryController` | Nested categories |
| `BrandController` | Brand CRUD |
| `InventoryController` | Stock view, adjustments |
| `OrderController` | Order management, status, invoice |
| `InvoiceController` | Billing lifecycle |
| `PaymentController` | Payment records |
| `CouponController` | Discount codes |
| `ShippingController` | Shipments, labels, tracking events |
| `CustomerController` | Customer profiles |
| `UserController` | Staff users |
| `ReviewController` | Moderation |
| `ReportController` | ERP reports + exports |
| `SettingsController` | Site settings |

### Storefront (`app/Http/Controllers/Storefront/`)

| Controller | Responsibility |
|------------|----------------|
| `HomeController` | Homepage |
| `ProductController` | Shop listing, product detail |
| `CategoryController` | Category pages |
| `CartController` | Cart CRUD |
| `CheckoutController` | Checkout flow |
| `PaymentController` | Payment instructions |
| `WishlistController` | Wishlist |
| `AccountController` | Customer dashboard/orders |
| `OrderTrackingController` | Guest order tracking |
| `PageController` | About, contact |
| `CouponController` | Apply coupon to cart |

### Auth (`app/Http/Controllers/Auth/`) — Laravel Breeze controllers

---

## 4. Models (54)

### Commerce
`Order`, `OrderItem`, `OrderStatusHistory`, `Cart`, `CartItem`, `Coupon`, `Payment`, `Refund`, `Invoice`, `InvoiceItem`, `Shipment`, `Shipping`, `ReturnRequest`, `Wishlist`

### Catalog
`Product`, `ProductVariant`, `ProductImage`, `ProductVideo`, `ProductDocument`, `Category`, `Brand`, `Collection`, `Attribute`, `AttributeValue`, `Banner`

### People
`User`, `Customer`, `Address`

### Inventory
`Inventory`, `Warehouse`, `StockMovement`

### CMS / Support
`Page`, `Blog`, `BlogCategory`, `Faq`, `Dealer`, `Inquiry`, `Review`, `SeoMetadata`, `SupportTicket`

### System
`ActivityLog`, `LoginAttempt`, `Notification`, `NotificationPreference`, `SiteSetting`, `PaymentWebhookLog`

### Model concerns (`app/Models/Concerns/`)
`FormatsMoney`, `GeneratesSlug`, `NormalizesStrings`

### Enums (`app/Enums/`)
`OrderStatus`, `PaymentStatus`, `PaymentMethod`, `ProductStatus`, `CustomerType`, `AddressType`, `InvoiceStatus`, `ShipmentStatus`, `ReviewStatus`, `CouponType`, `InquiryStatus`, `StockMovementType`

---

## 5. Services

### Storefront domain
| Service | Purpose |
|---------|---------|
| `CartService` | Session/guest + auth cart |
| `CheckoutService` | Place order transaction |
| `CheckoutPricingService` | Tax, shipping, coupon totals |
| `CouponService` | Validate/apply coupons |
| `PaymentService` | Initiate gateway payments |
| `ProductCatalogService` | Filters, search, pagination |
| `WishlistService` | Wishlist CRUD |
| `ActivityLogService` | Audit trail |

### Admin domain (`Services/Admin/`)
| Service | Purpose |
|---------|---------|
| `DashboardService` | Admin KPIs |
| `ProductService` | Product CRUD orchestration |
| `ProductVariantService` | Variant management |
| `ProductImageService` | Image uploads |
| `CategoryService` | Category tree |
| `BrandService` | Brands |
| `InventoryService` | Stock operations |
| `OrderService` | Order lifecycle |
| `OrderNotificationService` | Customer notifications |
| `InvoiceService` | Invoice generation |
| `ShippingService` | Fulfillment |
| `CustomerService` | Customer admin |
| `ReportService` | ERP report queries |
| `ReportExportService` | CSV, Excel, PDF exports |

### Payments (`Services/Payments/`)
`PaymentGatewayManager`, `BasePaymentGateway`, `CashOnDeliveryGateway`, `BankTransferGateway`, `StripeGateway`, `JazzCashGateway`, `EasypaisaGateway`, `PayFastGateway`

**Pattern:** Controllers → Services → Repositories/Models. No business logic in controllers.

---

## 6. Repositories

### Interface (`Repositories/Contracts/RepositoryInterface.php`)
`find`, `findOrFail`, `all`, `paginate`, `create`, `update`, `delete`

### Domain interfaces + Eloquent implementations
| Interface | Implementation |
|-----------|----------------|
| `ProductRepositoryInterface` | `ProductRepository` |
| `OrderRepositoryInterface` | `OrderRepository` |
| `CategoryRepositoryInterface` | `CategoryRepository` |
| `BrandRepositoryInterface` | `BrandRepository` |
| `InventoryRepositoryInterface` | `InventoryRepository` |
| `CustomerRepositoryInterface` | `CustomerRepository` |
| `InvoiceRepositoryInterface` | `InvoiceRepository` |
| `ShippingRepositoryInterface` | `ShippingRepository` |
| `UserRepositoryInterface` | `UserRepository` |

Registered in `AppServiceProvider::register()`.

---

## 7. Policies

| Policy | Model | Permissions checked |
|--------|-------|---------------------|
| `ProductPolicy` | Product | view, create, update, delete |
| `CategoryPolicy` | Category | view, manage |
| `BrandPolicy` | Brand | view, manage |
| `InventoryPolicy` | Inventory | view, manage |
| `OrderPolicy` | Order | view, update, cancel |
| `CustomerPolicy` | Customer | view, manage |
| `InvoicePolicy` | Invoice | view, manage |
| `PaymentPolicy` | Payment | view |
| `CouponPolicy` | Coupon | view, manage |
| `ReviewPolicy` | Review | view, moderate |
| `ShippingPolicy` | Shipping | view, manage |
| `SiteSettingPolicy` | SiteSetting | view, manage |
| `UserPolicy` | User | view, manage |

`Gate::before` grants super-admin all abilities.  
Admin routes use Spatie permission middleware (`can:orders.view`).

---

## 8. Middleware

| Alias | Class | Purpose |
|-------|-------|---------|
| `staff` | `EnsureUserIsStaff` | Restrict `/admin` to staff roles |

Laravel 12 default stack: encryption, CSRF, session, auth, etc.  
Configured in `bootstrap/app.php`.

---

## 9. Form Requests

### Storefront
`CheckoutRequest`, `AddToCartRequest`, `UpdateCartItemRequest`, `ApplyCouponRequest`, `AddToWishlistRequest`, `ContactRequest`, `TrackOrderRequest`

### Admin
`StoreProductRequest`, `UpdateProductRequest`, `StoreCategoryRequest`, `UpdateCategoryRequest`, `StoreBrandRequest`, `UpdateBrandRequest`, `UpdateOrderStatusRequest`, `UpdateOrderPaymentStatusRequest`, `CancelOrderRequest`, `AdjustInventoryRequest`, `UpdateCustomerRequest`, `UpdateUserRequest`, `StoreShippingRequest`, `UpdateShippingRequest`, `StoreTrackingEventRequest`

### Auth / Profile
`LoginRequest`, `ProfileUpdateRequest`

---

## 10. Views

### Layouts
| File | Used by |
|------|---------|
| `layouts/admin.blade.php` | ERP admin |
| `layouts/storefront.blade.php` | Public shop |
| `layouts/guest.blade.php` | Auth pages |
| `layouts/app.blade.php` | Legacy Breeze |
| `layouts/print.blade.php` | Invoices, labels |

### Admin views (`resources/views/admin/`)
Dashboard, CRUD for products/categories/brands/customers/users/coupons, order/invoice/payment/shipping/review/inventory/report/settings views, partials (sidebar, stat-card, page-header, icon)

### Storefront views (`resources/views/storefront/`)
Home, shop, product detail, category, cart, wishlist, checkout (+ success), payment, account dashboard/orders, order tracking, about/contact, partials (header, footer, breadcrumbs, product-card, product-filters)

### Components (`resources/views/components/`)
Breeze UI primitives, `money`, `order-status-badge`, storefront components (`seo`, `product-card`, `add-to-cart`, `navigation`, etc.)

---

## 11. JavaScript

| File | Role |
|------|------|
| `resources/js/app.js` | Vite entry — Alpine.js bootstrap |
| `resources/js/bootstrap.js` | Axios defaults |
| `resources/js/admin/admin-shell.js` | Sidebar collapse/mobile drawer |
| `resources/js/admin/product-form.js` | Product variant tabs |

Charts on report pages load Chart.js via CDN in `@push('head')`.

---

## 12. Tailwind Configuration

**`tailwind.config.js`**
- Content: all Blade views + pagination + compiled views
- Font: Figtree (sans)
- Plugin: `@tailwindcss/forms`

**`resources/css/app.css`**
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
@import './admin-shell.css';
```

**`vite.config.js`**
```js
input: ['resources/css/app.css', 'resources/js/app.js']
```

Build: `npm run build` · Dev: `npm run dev`

---

## 13. Seeders

| Seeder | Data |
|--------|------|
| `RolesAndPermissionsSeeder` | Roles + Spatie permissions |
| `SiteSettingsSeeder` | Shop config |
| `AdminUserSeeder` | Super admin account |
| `WarehouseSeeder` | Warehouses |
| `BrandSeeder` | Brands |
| `CategorySeeder` | Category tree |
| `AttributeSeeder` | Product attributes |
| `ProductSeeder` | Demo products + variants |
| `InventorySeeder` | Stock levels |
| `DemoCustomerSeeder` | Sample customers |
| `OrderSeeder` | Sample orders |
| `CouponSeeder` | Discount codes |

Run: `php artisan db:seed` or `php artisan migrate:fresh --seed`

---

## 14. Factories

| Factory | Model |
|---------|-------|
| Factory | Model | States |
|---------|-------|--------|
| `UserFactory` | User | `unverified()`, `customer()` |
| `BrandFactory` | Brand | `inactive()` |
| `ProductFactory` | Product | `featured()`, `draft()` |
| `ProductVariantFactory` | ProductVariant | `outOfStock()` |
| `CustomerFactory` | Customer | `wholesale()`, `dealer()` |
| `OrderFactory` | Order | `paid()`, `guest()` |

---

## 19. REST API (Sanctum)

**Base URL:** `/api/v1` · **Auth:** Bearer token

| Method | Endpoint | Auth |
|--------|----------|------|
| POST | `/api/v1/auth/login` | Public |
| POST | `/api/v1/auth/register` | Public |
| GET | `/api/v1/auth/me` | Sanctum |
| POST | `/api/v1/auth/logout` | Sanctum |
| GET | `/api/v1/products` | Public |
| GET | `/api/v1/products/{slug}` | Public |
| GET | `/api/v1/categories/{slug}` | Public |
| GET | `/api/v1/orders` | Sanctum |
| GET | `/api/v1/orders/{order}` | Sanctum |

---

## 20. Payment Webhooks

| Method | Endpoint |
|--------|----------|
| POST | `/api/webhooks/payments/{gateway}` |

Gateway slugs: `jazzcash`, `easypaisa`, `stripe`, `payfast`

---

## 15. Tests

```
tests/
├── TestCase.php
├── Unit/ExampleTest.php
└── Feature/
    ├── AdminDashboardTest.php
    ├── ProfileTest.php
    ├── Admin/
    │   ├── CatalogManagementTest.php
    │   ├── CommerceManagementTest.php
    │   ├── OrderManagementTest.php
    │   ├── ProductManagementTest.php
    │   └── ReportManagementTest.php
    ├── Auth/                    # 6 Breeze auth tests
    └── Storefront/
        ├── StorefrontCheckoutTest.php
        └── StorefrontPagesTest.php
```

Run: `php artisan test`

---

## 16. Config Files

| File | Purpose |
|------|---------|
| `config/shop.php` | Currency, tax, shipping, admin email |
| `config/admin.php` | Sidebar, menu, permission map |
| `config/payments.php` | Gateway keys |
| `config/reports.php` | Report types |
| `config/permission.php` | Spatie config |

---

## 17. Architecture Patterns (Laravel 12)

```
HTTP Request
    → Middleware (auth, staff, can:*)
    → Controller (thin)
    → Form Request (validation)
    → Service (business logic)
    → Repository (queries) / Model (persistence)
    → Policy (authorization)
    → View / JSON response
```

**Dependency injection:** Constructor injection in controllers and services.  
**Transactions:** `DB::transaction()` in checkout and order flows.  
**Enums:** Backed enums for status fields (type-safe).  
**Permissions:** Spatie roles on User model; route-level `can:` middleware.

---

## 18. Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Admin: `/admin` (seeded admin user from `config/shop.admin_email`)  
Storefront: `/`
