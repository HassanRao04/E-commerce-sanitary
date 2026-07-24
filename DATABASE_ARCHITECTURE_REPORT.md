# Database Architecture Audit Report

**Project:** Sanitary ERP + Storefront (Laravel)  
**Database:** `sanitary_ecommerce`  
**Audit date:** 2026-07-24  
**Mode:** Read-only (no schema or application code was modified)

---

## 1. Database Overview

| Metric | Count |
|--------|------:|
| Application tables (`sanitary_ecommerce`) | **81** |
| Migration files (`database/migrations`) | **57** |
| Eloquent model classes (`app/Models`, excl. Concerns) | **67** |
| Model concern traits | **3** (`FormatsMoney`, `GeneratesSlug`, `NormalizesStrings`) |
| Declared foreign keys (DB) | **90** |
| Pure / composite pivot tables | **8** (see §2) |
| Soft-deleted tables | **12** |
| Observers (`app/Observers`) | **0** |
| Repository contracts + Eloquent repos | **20** files |

**Pivot / join tables**

| Table | Type |
|-------|------|
| `product_categories` | Composite PK pivot |
| `product_collections` | Composite PK pivot |
| `related_products` | Composite PK pivot |
| `role_has_permissions` | Spatie pivot |
| `model_has_roles` | Spatie morph pivot |
| `model_has_permissions` | Spatie morph pivot |
| `frequently_bought_together` | Join table with surrogate `id` |
| `product_attribute_values` / `product_variant_attribute_values` | Attribute assignment joins (surrogate `id`) |

---

## 2. Tables

Legend: **PK** = primary key · **SD** = soft deletes · **TS** = timestamps present.

### 2.1 Identity & access

| Table | Purpose | PK | Soft deletes | Timestamps | Notable FKs / uniques / indexes |
|-------|---------|----|--------------|------------|----------------------------------|
| `users` | Staff, customers, influencers | `id` | Yes | Yes | Unique `email`; Spatie roles via pivots |
| `password_reset_tokens` | Password reset | `email` | No | `created_at` | — |
| `sessions` | Web sessions | `id` | No | No | Nullable `user_id` (no FK in base Laravel migration) |
| `personal_access_tokens` | Sanctum API tokens | `id` | No | Yes | Morph `tokenable`; unique `token` |
| `permissions` | Spatie permissions | `id` | No | Yes | Unique name/guard |
| `roles` | Spatie roles | `id` | No | Yes | Unique name/guard |
| `model_has_roles` | User↔role | Composite | No | No | FK `role_id` → `roles` |
| `model_has_permissions` | Direct permissions | Composite | No | No | FK `permission_id` → `permissions` |
| `role_has_permissions` | Role↔permission | Composite | No | No | FKs to roles & permissions |
| `login_attempts` | Auth audit | `id` | No | No (`attempted_at`) | Index `(email, attempted_at)` |

### 2.2 Catalog

| Table | Purpose | PK | SD | TS | FKs / uniques |
|-------|---------|----|----|----|---------------|
| `categories` | Nested catalog tree | `id` | Yes | Yes | Nested set cols; unique `slug` |
| `brands` | Brands | `id` | Yes | Yes | Unique `slug` |
| `collections` | Merchandising groups | `id` | No | Yes | Unique `slug` |
| `banners` | Storefront banners | `id` | No | Yes | — |
| `attributes` | Variant/filter attrs | `id` | No | Yes | Unique `slug` |
| `attribute_values` | Attribute options | `id` | No | Yes | FK `attribute_id` CASCADE; unique `(attribute_id, slug)` |
| `products` | Product masters | `id` | Yes | Yes | FK `brand_id` NULL; FK `default_variant_id` NULL; unique `slug`, `base_sku` |
| `product_variants` | Sellable SKUs | `id` | Yes | Yes | FK `product_id` CASCADE; unique `sku` |
| `product_images` | Media | `id` | No | No | FKs product / variant |
| `product_videos` | Media | `id` | No | No | FK product CASCADE |
| `product_documents` | Specs/PDFs | `id` | No | No | FK product CASCADE |
| `product_categories` | Product↔category | `(product_id, category_id)` | No | No | Both FKs CASCADE |
| `product_collections` | Product↔collection | Composite | No | No | Both FKs CASCADE |
| `product_attribute_values` | Product attrs | `id` | No | No | FKs product/attribute/value |
| `product_variant_attribute_values` | Variant attrs | `id` | No | No | FKs variant/attribute/value |
| `related_products` | Related SKUs | Composite | No | No | Both → `products` CASCADE |
| `frequently_bought_together` | FBT links | `id` | No | No | Unique `(product_id, related_product_id)` |
| `product_offers` | Buy-X discounts | `id` | No | Yes | FK `product_id` |
| `product_pipe_length_options` | Pipe length options | `id` | No | Yes | FK `product_id` |
| `product_shipping_rates` | Per-product shipping | `id` | No | Yes | FK `product_id`; free-shipping flag |

### 2.3 Commerce / cart

| Table | Purpose | PK | SD | TS | FKs / uniques |
|-------|---------|----|----|----|---------------|
| `addresses` | User addresses | `id` | No | Yes | FK `user_id` CASCADE |
| `carts` | Carts | `id` | No | Yes | FK user NULL; FK coupon NULL; unique `session_id` |
| `cart_items` | Cart lines | `id` | No | Yes | FKs cart/product/variant CASCADE; offer & pipe-length FKs; unique `(cart_id, product_variant_id)` |
| `wishlists` | Wishlists | `id` | No | No | Unique `(user_id, product_id)`; FKs user/product/variant |
| `compare_lists` | Compare sessions | `id` | No | Yes | Unique user / session |
| `compare_items` | Compare lines | `id` | No | No | FKs list/product/variant |
| `coupons` | Discount + influencer | `id` | Yes | Yes | Unique `code`; FK `influencer_id` → users |

### 2.4 Orders & fulfillment

| Table | Purpose | PK | SD | TS | FKs / uniques |
|-------|---------|----|----|----|---------------|
| `orders` | Sales orders | `id` | Yes | Yes | Unique `order_number`, `tracking_token`; FKs user, addresses, coupon, influencer |
| `order_items` | Line items (snapshots) | `id` | No | No | FKs order CASCADE; product/variant NULL |
| `order_statuses` | ERP status catalog | `id` | No | Yes | Unique `slug` (logical) |
| `order_status_histories` | Status timeline | `id` | No | Yes | FK `order_id` CASCADE; `status` string (no FK) |
| `order_number_sequences` | Number generation | `id` | No | Yes | — |
| `shipments` | Shipments | `id` | No | Yes | FK `order_id` CASCADE |
| `shipment_tracking_events` | Tracking events | `id` | No | Yes | FK `shipment_id` |
| `return_requests` | Returns | `id` | No | Yes | FKs order CASCADE; user NULL |
| `refunds` | Refunds | `id` | No | Yes | FKs order CASCADE; payment NULL |

**Orders nullable highlights:** `user_id`, address IDs, `payment_method`, `coupon_code`/`coupon_id`, `influencer_id`, commission fields, `notes`, etc.

### 2.5 Payments & invoices

| Table | Purpose | PK | SD | TS | FKs / indexes |
|-------|---------|----|----|----|---------------|
| `payments` | Gateway payments | `id` | No | Yes | FK order CASCADE; indexes `transaction_id`, `status` |
| `payment_webhook_logs` | Raw webhooks | `id` | No | Yes | No order FK |
| `invoices` | Invoices | `id` | Yes | Yes | Unique `order_id`, `invoice_number` |
| `invoice_items` | Invoice lines | `id` | No | Yes | FK invoice CASCADE |

### 2.6 Inventory

| Table | Purpose | PK | SD | TS | FKs / uniques |
|-------|---------|----|----|----|---------------|
| `warehouses` | Warehouses | `id` | Yes | Yes | Unique `code`; indexes `is_default`, `is_active` |
| `inventory_items` | On-hand / reserved | `id` | No | Yes | Unique `(warehouse_id, product_variant_id)` |
| `stock_movements` | Stock ledger | `id` | No | `created_at` only | FKs warehouse/variant; morph `reference_*`; `performed_by` → users |

### 2.7 Customers, CMS, support

| Table | Purpose | PK | SD | TS | Notes |
|-------|---------|----|----|----|-------|
| `customer_profiles` | Customer ERP profile | `id` | Yes | Yes | FK `user_id` |
| `dealers` | Dealer applications | `id` | No | Yes | **No user FK** |
| `reviews` | Product reviews | `id` | No | Yes | FKs user/product/order/order_item; unique review per order item |
| `review_images` | Review media | `id` | No | Yes | FK review |
| `review_settings` | Review config | `id` | No | Yes | Singleton-style |
| `pages` | CMS pages | `id` | No | Yes | Unique `slug` |
| `blog_categories` | Blog cats | `id` | No | Yes | Unique `slug` |
| `blogs` | Blog posts | `id` | No | Yes | FKs category/author |
| `faqs` | FAQs | `id` | No | Yes | — |
| `inquiries` | Contact messages | `id` | No | Yes | FK `assigned_to`; source/IP fields |
| `support_tickets` | Support | `id` | No | Yes | FKs user/order/assignee |
| `seo_metadata` | Polymorphic SEO | `id` | No | Yes | `model_type` + `model_id` |
| `newsletter_subscribers` | Newsletter | `id` | No | Yes | Unique email (seeder/migration) |
| `site_settings` | Singleton settings | `id` | No | Yes | Many nullable CMS/contact fields |
| `notifications` | In-app notifications | `id` | No | Yes | FK user CASCADE |
| `notification_preferences` | Prefs | `id` | No | Yes | Unique `user_id` |
| `activity_logs` | Audit log | `id` | Yes | `created_at` | FK user NULL; morph index |

### 2.8 Settings (shipping / tax / checkout)

| Table | Purpose | PK | SD | TS |
|-------|---------|----|----|----|
| `shipping_settings` | Global shipping rules | `id` | No | Yes |
| `category_shipping_rates` | Category rates | `id` | No | Yes |
| `tax_charge_settings` | Tax/service/handling | `id` | No | Yes |
| `checkout_rules_settings` | Checkout rules | `id` | No | Yes |

### 2.9 Influencer commissions

| Table | Purpose | PK | SD | TS | FKs / uniques |
|-------|---------|----|----|----|---------------|
| `influencer_commission_transactions` | Ledger credits/debits | `id` | No | Yes | Unique `order_id` (credit pointer); FKs influencer, order, reference_order, created_by; optional `transaction_id`, `admin_notes` |

### 2.10 Framework / queue

| Table | Purpose | PK |
|-------|---------|-----|
| `migrations` | Migration tracker | `id` |
| `cache` / `cache_locks` | Cache | `key` |
| `jobs` / `job_batches` / `failed_jobs` | Queue | `id` |

---

## 3. Relationships

### 3.1 Core domain graph (text)

```
User
 ├── hasOne Customer (customer_profiles)
 ├── hasMany Address, Order, Wishlist, Review, Notification, ActivityLog
 ├── hasOne Cart, CompareList, NotificationPreference
 ├── hasMany Coupon (as influencer_id)
 ├── hasMany Order (as influencer_id)
 └── hasMany InfluencerCommissionTransaction (as influencer_id)

Brand ──hasMany──> Product
Category <──belongsToMany──> Product  (product_categories)
Collection <──belongsToMany──> Product  (product_collections)

Product
 ├── belongsTo Brand
 ├── hasMany ProductVariant, ProductImage, ProductVideo, ProductDocument
 ├── hasMany ProductOffer, ProductPipeLengthOption
 ├── belongsTo default ProductVariant
 ├── morphOne SeoMetadata
 └── hasMany Review, OrderItem, Wishlist

ProductVariant
 ├── belongsTo Product
 ├── hasMany Inventory (inventory_items)
 └── hasMany StockMovement

Order
 ├── belongsTo User, Customer (via user_id), Coupon, Influencer(User)
 ├── belongsTo Address (billing/shipping)
 ├── belongsTo OrderStatus (status slug — Eloquent only)
 ├── hasMany OrderItem, Payment, Shipping, Refund, ReturnRequest, Review
 ├── hasOne Invoice
 └── hasOne InfluencerCommissionTransaction (credit)

Cart ──hasMany──> CartItem ──belongsTo──> Product / Variant / Offer / PipeLength
Invoice ──hasMany──> InvoiceItem
Shipping (shipments) ──hasMany──> Tracking (shipment_tracking_events)
Payment ──hasMany──> Refund
Warehouse ──hasMany──> InventoryItem, StockMovement
StockMovement ──morphTo──> reference
```

### 3.2 Model relationship inventory (summary)

| Model | belongsTo | hasOne / hasMany | belongsToMany / morph |
|-------|-----------|------------------|------------------------|
| **User** | — | Customer, Cart, CompareList, NotificationPreference; many Orders, Addresses, Reviews, Wishlists, Notifications, ActivityLogs; influencer Coupons/Orders/Ledger | Spatie roles/permissions |
| **Customer** | User | hasManyThrough Orders | — |
| **Product** | Brand, default Variant | Variants, Images, Videos, Documents, Attrs, Offers, PipeLengths, Reviews, OrderItems, Wishlists | Categories, Collections, Related, FBT; morphOne Seo |
| **ProductVariant** | Product | Images, Attr values, Inventory, StockMovements | — |
| **Order** | User, Customer, Addresses, Status, Coupons, Influencer | Items, Histories, Payments, Shipments, Returns, Refunds, Reviews; Invoice; Ledger credit | — |
| **OrderItem** | Order, Product, Variant | Review | — |
| **Coupon** | Influencer (User) | Carts, tracked Orders | — |
| **Cart / CartItem** | User/Coupon; Cart/Product/Variant/Offer/Pipe | Items | — |
| **Invoice / InvoiceItem** | Order; Invoice/Product/Variant | Items | — |
| **Payment / Refund** | Order; Order/Payment | Refunds | — |
| **Shipping / Tracking** | Order; Shipping | Tracking events | — |
| **Inventory / Warehouse** | Warehouse, Variant | Items, Movements | — |
| **StockMovement** | Warehouse, Variant, User | — | morphTo reference |
| **InfluencerCommissionTransaction** | Influencer, Order, referenceOrder, creator | — | — |
| **Review** | User, Product, Order, OrderItem | Images | — |
| **Category** | parent Category | children | Products |
| **Brand** | — | Products | — |
| **SeoMetadata** | — | — | morphTo |
| **ActivityLog** | User | — | morph-like model_type/id (not Eloquent morphTo on model) |

Alias models: `InventoryItem` extends `Inventory`; `ShipmentTrackingEvent` extends `Tracking`. Table aliases: `Customer` → `customer_profiles`, `Shipping` → `shipments`, `Tracking` → `shipment_tracking_events`, `Inventory` → `inventory_items`.

---

## 4. Model Analysis

### 4.1 Cross-cutting patterns

| Concern | Pattern in this codebase |
|---------|--------------------------|
| **Fillable** | Nearly all models use explicit `$fillable` (not `$guarded = []`) |
| **Hidden** | Primarily `User` (`password`, `remember_token`) |
| **Casts** | Modern `casts()` method; enums for payment/status/coupon/ledger; decimals for money |
| **Appends** | Rarely used as `$appends`; many computed `Attribute` accessors instead |
| **Traits** | SoftDeletes on catalog/order/user/coupon/invoice/warehouse/activity; FormatsMoney; HasFactory; HasRoles (User); HasApiTokens |
| **Observers** | **None** registered under `app/Observers` |
| **Scopes** | Heavy use of `#[Scope]` on Order, Product, User, Coupon, Payment, Review, Inventory, Shipping, etc. |
| **Accessors** | Widespread (`is_valid`, money formatters, display names) |
| **Mutators** | Limited (e.g. Coupon `code` uppercasing; User name composition in `booted`) |

### 4.2 SoftDeletes models

`User`, `Product`, `ProductVariant`, `Brand`, `Category`, `Order`, `Coupon`, `Invoice`, `Warehouse`, `Customer`, `ActivityLog` (+ DB column on those tables).

### 4.3 Notable `booted()` logic

- **User:** composes `name` from first/last; syncs `suspended_at` with status.
- **Order:** generates tracking token / hooks profile service (application-level, not DB trigger).

### 4.4 Sensitive fillable note

`User::$fillable` includes `password` — acceptable with `'password' => 'hashed'` cast; still requires careful FormRequest filtering (see §10).

---

## 5. Migration Analysis

### 5.1 Foundation (Laravel + users)

| Migration | Purpose |
|-----------|---------|
| `0001_01_01_000000_create_users_table` | users, password_reset_tokens, sessions |
| `0001_01_01_000001_create_cache_table` | cache, cache_locks |
| `0001_01_01_000002_create_jobs_table` | jobs, job_batches, failed_jobs |
| `2026_06_25_100000_extend_users_table` | phone, status, verification, last_login |
| `2026_06_25_213137_create_permission_tables` | Spatie ACL |
| `2026_06_26_100000_create_personal_access_tokens_table` | Sanctum |
| `2026_06_26_130001_enhance_users_table...` | Enterprise user fields |
| `2026_07_22_203358_add_notes_to_users_table` | Admin notes |

### 5.2 Catalog & products

| Migration | Creates / updates |
|-----------|-------------------|
| `...100001_create_catalog_tables` | categories (nested set), brands, collections, banners |
| `...100002_create_attributes_tables` | attributes, attribute_values |
| `...100003_create_products_tables` | products, variants, images, videos, documents + default_variant FK |
| `...100004_create_product_relation_tables` | category/collection pivots, attr joins, related, FBT |
| Later alters | color_hex, wholesale prices, offers, pipe lengths, option titles |

### 5.3 Commerce & orders

| Migration | Creates / updates |
|-----------|-------------------|
| `...100005_create_commerce_tables` | addresses, carts, cart_items, wishlists, compare_*, coupons |
| `...100006_create_orders_tables` | orders, order_items, histories, shipments, returns |
| `...100007_create_payments_tables` | payments, webhook logs, refunds |
| `...100010_add_order_tracking_token` | tracking_token |
| Soft-delete / address FKs | `...100005_add_soft_deletes_and_schema_enhancements` |
| Charges / tax | tax_charge_settings; order charge columns |
| ERP statuses | order_statuses + data migrate to slugs |
| Influencer | coupon influencer fields; order coupon/influencer/commission; ledger table + reference_order + transaction_id |

### 5.4 Inventory & invoices

| Migration | Creates |
|-----------|---------|
| `...100002_create_warehouses_and_inventory_tables` | warehouses, inventory_items, stock_movements |
| `...100003_create_invoices_tables` | invoices, invoice_items |
| `...100004_create_shipment_tracking_events_table` | tracking events |
| `...120001_create_order_number_sequences_table` | sequences |

### 5.5 CMS, settings, contact

| Migration | Creates / updates |
|-----------|-------------------|
| `...100008_create_cms_and_support_tables` | reviews, pages, blogs, faqs, inquiries, dealers, tickets, seo |
| `...100009_create_system_tables` | activity_logs, site_settings, notifications, prefs, login_attempts |
| `...100001_create_customer_profiles_table` | customer_profiles |
| Shipping settings | shipping_settings, category_shipping_rates |
| Site settings alters | homepage, header, storefront, contact notifications, auto-reply |
| Reviews enhance | order_item_id + unique constraint |

### 5.6 Cascade conventions (typical)

| Pattern | Usage |
|---------|--------|
| `cascadeOnDelete()` | Child rows of products, carts, orders, invoices, inventory lines |
| `nullOnDelete()` | Optional parents (brand on product, user on guest order, coupon on cart) |
| Morph columns | No DB FK (by design) |

---

## 6. Foreign Key Validation

### 6.1 Strengths

- **90** real FKs covering most `*_id` commerce/catalog/inventory links.
- Consistent CASCADE for owned children; NULL for optional associations.
- Unique constraints protect cart lines, wishlists, invoice↔order, coupon codes, SKUs.

### 6.2 Missing foreign keys (by design or gap)

| Column / link | Issue |
|---------------|--------|
| `orders.status` → `order_statuses.slug` | **Eloquent-only**; no DB FK (slug string) |
| `order_status_histories.status` | Same — no FK to `order_statuses` |
| `sessions.user_id` | Laravel default; typically no FK |
| `activity_logs.model_type/model_id` | Polymorphic — no FK |
| `stock_movements.reference_*` | Polymorphic — no FK |
| `seo_metadata.model_*` | Polymorphic — no FK |
| `personal_access_tokens.tokenable_*` | Polymorphic — no FK |
| `payment_webhook_logs` | No link to `payments`/`orders` |
| `dealers` | No `user_id` / customer link |

### 6.3 Wrong / risky FKs

- None clearly **wrong** among declared FKs.
- `orders.coupon_code` also has Eloquent `belongsTo(Coupon::class, 'coupon_code', 'code')` **in addition to** `coupon_id` — dual association; prefer `coupon_id` as source of truth.

### 6.4 Nullable FK notes

- Guest checkout: `orders.user_id` nullable — correct.
- Soft-deleted parents with `nullOnDelete` vs CASCADE: deleting a brand nulls `products.brand_id` — correct; hard-deleting a product cascades variants/images — correct.
- Soft deletes do **not** fire DB CASCADE; orphans avoided only if app uses SoftDeletes consistently.

### 6.5 Cascade issues

- Hard-deleting an `Order` cascades payments, items, shipments — intended for ERP cleanup; soft delete is preferred in app.
- `product_variants` CASCADE from products: expected.

---

## 7. Data Integrity

### 7.1 Orphan risk (application-level)

| Risk | Why |
|------|-----|
| `orders.status` not in `order_statuses` | No FK; bad slug breaks workflow UI |
| Soft-deleted Product still referenced by OrderItem | OrderItem keeps snapshot fields — OK; live product_id may point to soft-deleted row |
| Dual coupon identity | `coupon_code` string vs `coupon_id` can diverge if updated independently |
| `product_variants.stock_quantity` vs `inventory_items` | Two stock sources — can drift |

### 7.2 Duplicate data (intentional denormalization)

- Order / invoice line **name, SKU, prices** snapshots — correct ERP practice.
- Customer name/email/phone copied onto `orders` — supports guest + history.
- Variant `stock_quantity` alongside warehouse inventory — **integrity smell**.

### 7.3 Missing constraints

| Gap | Recommendation (informational only) |
|-----|-------------------------------------|
| FK or CHECK for order status slugs | Enforce against `order_statuses` |
| Unique `(user_id)` on dealers if converted to accounts | If dealers become users |
| Webhook log → payment reference | Traceability |

### 7.4 Unique keys present (good)

Coupon codes, order numbers, tracking tokens, SKUs, slugs, invoice numbers, cart line uniqueness, review-per-order-item, influencer credit `order_id` uniqueness.

---

## 8. Normalization

### 1NF
Mostly satisfied (atomic columns). JSON used appropriately for `metadata`, `social_links`, activity old/new values.

### 2NF
Composite pivots only store keys — good. Surrogate-key join tables are fine.

### 3NF / pragmatic denormalization

| Area | Assessment |
|------|------------|
| Order line snapshots | Acceptable denormalization |
| Order customer_* fields | Acceptable for guests/history |
| Site settings wide row | Singleton EAV alternative; OK for CMS |
| **Variant stock + inventory_items** | Violates single source of truth |
| Finish/color/size on variants **and** attribute_value tables | Partial overlap / transitional model |

**Normalization problems to note:** dual stock; dual coupon reference; status as free string vs status table.

---

## 9. Performance

### 9.1 Indexes present

- High-traffic FKs via InnoDB FK indexes.
- Extra indexes: payments status/transaction_id, reviews (product_id, status), login_attempts, activity morph, inventory on_hand, stock_movements composites, warehouses flags, shortened ledger indexes (`ict_*`).

### 9.2 Missing / optional indexes

| Candidate | Why |
|-----------|-----|
| `orders(influencer_id, influencer_commission_paid_at)` | Influencer performance / payout reports |
| `orders(status, created_at)` | Admin order lists |
| `orders(payment_status, created_at)` | Revenue reports |
| `carts(user_id)` | Already FK-indexed typically |
| `inquiries(status, created_at)` | Admin inbox |

### 9.3 N+1 risks

| Area | Risk |
|------|------|
| Admin order index without `with(['items','user','payments'])` | Classic N+1 |
| Product listing without default variant / images | N+1 |
| Influencer ledger with order + creator | Mitigated when eager-loaded in CouponService |
| Nested category trees | Nested set helps reads |

### 9.4 Large-table growth

Expect growth in: `orders`, `order_items`, `stock_movements`, `activity_logs`, `payments`, `shipment_tracking_events`, `influencer_commission_transactions`. Partitioning not required yet; archive strategy recommended later for activity_logs / webhook logs.

### 9.5 Optimization opportunities

- Prefer `coupon_id` in queries over `coupon_code`.
- Reports already clone query builders — keep covering indexes aligned with date filters.
- Avoid selecting unused JSON columns on list endpoints.

---

## 10. Security

| Topic | Status |
|-------|--------|
| **Mass assignment** | Explicit `$fillable` everywhere reviewed — good |
| **Hidden attributes** | Passwords/tokens hidden on User |
| **Password storage** | Hashed cast on User |
| **API tokens** | Sanctum `personal_access_tokens`; token hashed |
| **Sensitive data** | Payment metadata JSON — ensure no raw PAN storage (gateway refs only) |
| **Soft deletes** | Key commercial entities soft-deletable |
| **ACL** | Spatie permissions; policies for coupons, inventory, shipping, inquiries |
| **Activity logs** | Soft-deletable audit trail with IP/UA |
| **Login attempts** | Brute-force visibility |
| **Influencer privacy** | Customer PII removed from influencer orders UI (view-level); DB still has customer_* on orders for admin |

**Watchouts:** `password` in fillable; ensure all update paths use FormRequests; webhook payloads may contain PII — restrict admin access.

---

## 11. ERP Module Mapping

### Catalog / Brands
`brands`, `categories`, `collections`, `attributes`, `attribute_values`, `banners`

### Products
`products`, `product_variants`, `product_images`, `product_videos`, `product_documents`, `product_categories`, `product_collections`, `product_attribute_values`, `product_variant_attribute_values`, `related_products`, `frequently_bought_together`, `product_offers`, `product_pipe_length_options`, `product_shipping_rates`, `seo_metadata`

### Cart & promotions
`carts`, `cart_items`, `coupons`, `wishlists`, `compare_lists`, `compare_items`

### Orders
`orders`, `order_items`, `order_statuses`, `order_status_histories`, `order_number_sequences`, `addresses`

### Payments
`payments`, `payment_webhook_logs`, `refunds`

### Invoices
`invoices`, `invoice_items`

### Shipping / fulfillment
`shipments`, `shipment_tracking_events`, `shipping_settings`, `category_shipping_rates`, `return_requests`

### Inventory
`warehouses`, `inventory_items`, `stock_movements`

### Customers & dealers
`users`, `customer_profiles`, `dealers`, `addresses`

### Influencer / commissions
`coupons` (influencer fields), `orders` (attribution/commission), `influencer_commission_transactions`

### Tax & checkout rules
`tax_charge_settings`, `checkout_rules_settings`

### CMS & support
`pages`, `blogs`, `blog_categories`, `faqs`, `inquiries`, `support_tickets`, `reviews`, `review_images`, `review_settings`, `newsletter_subscribers`, `site_settings`

### Notifications & audit
`notifications`, `notification_preferences`, `activity_logs`, `login_attempts`

### Identity / platform
`users`, Spatie tables, `personal_access_tokens`, `sessions`, `password_reset_tokens`, `cache*`, `jobs*`

---

## 12. Missing Relationships

| Gap | Detail |
|-----|--------|
| DB FK Order ↔ OrderStatus | Only Eloquent `belongsTo` on slug |
| Dealer ↔ User | Table exists; no model relation / FK |
| PaymentWebhookLog ↔ Payment/Order | No Eloquent relation or FK |
| ActivityLog morphTo | Columns exist; formal `morphTo` may be incomplete vs generic fields |
| Order dual coupon relations | Two belongsTo paths (`coupon_id` and `coupon_code`) — consolidate conceptually |
| Inventory sync with Variant.stock_quantity | No enforced relationship/invariant |
| SiteSetting | Singleton; no relations (OK) |

---

## 13. Unused / low-use Artifacts

### Tables
All 81 tables are referenced by migrations and have plausible app use. **Lower traffic / satellite:** `dealers`, `newsletter_subscribers`, `compare_*`, `payment_webhook_logs`, `faqs`, `blogs` (depending on feature enablement).

### Models
- `Dealer` — thin model; confirm admin UI coverage.
- `InventoryItem` / `ShipmentTrackingEvent` — aliases of primary models (not unused).
- Concerns are traits, not tables.

### Migrations
No unused migration files detected; all 57 participate in schema history. Data-only migrations (`migrate_orders_to_erp_status_slugs`, inquiry status normalize) are intentional.

### Alternate DB
`Schema::getTableListing()` also surfaced `phpmyadmin.*` and `sanitary_store.*` on the server — **outside** this app’s primary schema; not part of ERP migrations.

---

## 14. Database Health Score

### **Score: 84 / 100**

| Band | Points | Notes |
|------|-------:|-------|
| Relational coverage & FKs | 22/25 | Strong FK graph; status slug & a few satellites lack FKs |
| Module clarity | 12/12 | Clear ERP module boundaries |
| Integrity constraints | 12/15 | Good uniques; dual stock/coupon/status weaken integrity |
| Soft deletes & audit | 8/8 | Mature soft-delete + activity/login audit |
| Performance readiness | 10/12 | Solid indexes; add composite indexes for influencer/order lists |
| Security posture | 10/12 | Hashed passwords, hidden secrets, ACL; watch fillable password & webhook JSON |
| Normalization pragmatism | 10/12 | Sensible snapshots; stock duplication is main smell |
| Documentation / consistency | — | Naming mostly consistent; Shipping/Tracking aliases clear |

### Why not higher?

1. Order status not enforced at DB level.  
2. Dual inventory quantity fields.  
3. Dual coupon references on orders.  
4. Dealers / webhook logs weakly integrated.  
5. **Fresh-migrate risk:** `cart_items` offer/pipe FKs are added in a migration that runs *before* `product_offers` / `product_pipe_length_options` are created (`145823` before `194500`/`195200`).  
6. **`cart_items` unique `(cart_id, product_variant_id)`** can conflict with multiple lines for the same variant (different offer/pipe length).  

### Why not lower?

Broad FK coverage, soft deletes on commercial cores, influencer ledger without duplicating commission amounts on debit credits, Spatie ACL, and clear migration chronology for a full ERP + storefront.

---

## Appendix A — Foreign key inventory (live DB)

90 FKs confirmed via `information_schema` (sample pattern):

- `orders.user_id` → `users.id`
- `orders.coupon_id` → `coupons.id`
- `orders.influencer_id` → `users.id`
- `orders.billing_address_id` / `shipping_address_id` → `addresses.id`
- `order_items.*` → orders/products/variants
- `influencer_commission_transactions.*` → users/orders
- Catalog/inventory/payment/invoice FKs as listed in §2

Full dump available by re-running information_schema query against `DATABASE()`.

---

## Appendix B — Counts checklist

| Item | Value |
|------|------:|
| Tables | 81 |
| Migrations | 57 |
| Models (excl. Concerns) | 67 |
| Pivot / morph-join tables | 8+ |
| Soft-delete tables | 12 |
| Foreign keys | 90 |
| Observers | 0 |

---

## Appendix C — Subagent corroboration

Additional detail from parallel audits ([migration schema](ce99fbc5-cb9e-40d8-89dc-e2f69cbeaaf1), [model inventory](cd1b37df-3de0-4266-8bda-22a6a84e6ee4)):

- **67** Eloquent models; no custom `addGlobalScope` (SoftDeletes + Category NestedSet only).  
- Deprecated aliases: `CustomerProfile`, `InventoryItem`, `Shipment`, `ShipmentTrackingEvent`.  
- Likely sparse models: `Dealer`, `Faq`, `Blog`/`BlogCategory`, `Page`, `SupportTicket` (light wiring).  
- No `$appends` / `$guarded` usage; casts via `casts()` method.  
- Soft-delete coverage is uneven (e.g. collections/banners/payments/ledger not soft-deleted).  

---

*End of report. Generated read-only for architecture review; no application or schema changes were made.*
