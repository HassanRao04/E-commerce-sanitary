# Phase 4 — Storefront, Checkout & Payments, Coupons

Phase 4 delivers the customer-facing commerce experience and coupon management.

## Modules

| Module | Status |
|--------|--------|
| Storefront catalog | Home, shop, categories, product detail |
| Cart | Guest + authenticated carts, stock validation |
| Checkout | Guest/customer checkout, tax, shipping, coupons |
| Payments | COD, bank transfer + gateway stubs (Stripe, JazzCash, etc.) |
| Coupons (storefront) | Apply/remove on cart and checkout |
| Coupons (admin) | Full CRUD at `/admin/coupons` |

## Storefront routes

| Route | Purpose |
|-------|---------|
| `/` | Home |
| `/shop` | Product listing with filters |
| `/products/{slug}` | Product detail |
| `/categories/{slug}` | Category page |
| `/cart` | Shopping cart |
| `/checkout` | Checkout |
| `/checkout/success/{order}` | Order confirmation |
| `/payment/{order}` | Bank transfer / payment instructions |
| `/wishlist` | Wishlist |
| `/account` | Customer dashboard |
| `/track-order` | Guest order tracking |

## Services

- `CartService` — session + user carts
- `CheckoutService` — place order transaction
- `CheckoutPricingService` — tax, shipping, discounts
- `CouponService` — validate and apply coupons
- `PaymentService` — initiate payments, apply webhooks
- `PaymentGatewayManager` — COD, bank transfer, Stripe, JazzCash, Easypaisa, PayFast

## Demo coupons

| Code | Type | Value |
|------|------|-------|
| `WELCOME10` | Percent | 10% off (min Rs. 5,000) |
| `FLAT500` | Fixed | Rs. 500 off (min Rs. 3,000) |
| `PROJECT15` | Percent | 15% off (min Rs. 25,000) |

## Payment config

Set in `.env`:

```
PAYMENT_COD_ENABLED=true
PAYMENT_BANK_TRANSFER_ENABLED=true
PAYMENT_STRIPE_ENABLED=false
```

Bank details: `BANK_ACCOUNT_NAME`, `BANK_ACCOUNT_NUMBER`, `BANK_NAME`, `BANK_IBAN`

## API & webhooks (extended)

- REST API: `/api/v1/products`, `/api/v1/auth/login`, `/api/v1/orders`
- Payment webhooks: `POST /api/webhooks/payments/{gateway}`

## Verify

```powershell
php artisan erp:verify-phase4
php artisan test --filter=Storefront
php artisan test --filter=CouponManagementTest
```

## Test flow

1. Visit `/shop` and add a product to cart
2. Apply coupon `WELCOME10` on cart or checkout
3. Complete checkout with Cash on Delivery
4. View order at `/checkout/success/{order}` or admin `/admin/orders`
