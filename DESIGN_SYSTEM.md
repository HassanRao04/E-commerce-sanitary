# Design System — Sanitary Store

Premium commerce design language inspired by **Apple** (clarity), **Nike** (confidence), **Shopify Plus** (structure), and **Gymshark** (energy).

This foundation is CSS + Tailwind only. Existing Blade views and business logic are unchanged until you opt in to `ds-*` classes.

---

## 1. Color Palette

### Core tokens

| Token | Hex | Usage |
|-------|-----|--------|
| `ink` | `#0B0B0F` | Primary text, primary buttons, nav |
| `ink-500` | `#636366` | Body text |
| `ink-100` | `#E8E8ED` | Borders, dividers |
| `surface` | `#FFFFFF` | Cards, inputs |
| `surface-subtle` | `#FAFAFA` | Page background |
| `surface-muted` | `#F5F5F7` | Sections, table headers |
| `accent` | `#0071E3` | Links, focus rings, secondary CTAs |
| `commerce-sale` | `#E11900` | Sale badges, promos |
| `success` | `#0A7A44` | Confirmations, in-stock |
| `warning` | `#B45309` | Low stock, pending |
| `danger` | `#DC2626` | Errors, destructive actions |

### Tailwind usage

```html
<div class="bg-surface-subtle text-ink border border-ink-100">
<button class="bg-ink text-white hover:bg-ink-800">
<span class="text-accent hover:text-accent-hover">
```

### CSS variables

All tokens are mirrored in `resources/css/design-system/tokens.css` as `--ds-color-*` for custom components.

---

## 2. Typography

### Font stack (recommended)

| Role | Font | Fallback |
|------|------|----------|
| **Primary UI** | [Instrument Sans](https://fonts.bunny.net/family/instrument-sans) | Inter, SF Pro, system-ui |
| **Display / hero** | Instrument Sans (semibold, tight tracking) | Same |
| **Monospace** | SF Mono / JetBrains Mono | For SKUs, order IDs |

**Load fonts (storefront already uses Bunny):**

```html
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet">
```

### Type scale

| Class | Size | Use |
|-------|------|-----|
| `.ds-display-xl` | 48–60px | Hero headlines |
| `.ds-display-lg` | 36–48px | Campaign titles |
| `.ds-heading-1` | 30–36px | Page titles |
| `.ds-heading-2` | 24–30px | Section titles |
| `.ds-heading-3` | 20px | Card titles |
| `.ds-body` | 16px | Paragraphs |
| `.ds-body-sm` | 14px | Meta, captions |
| `.ds-caption` | 12px uppercase | Labels, eyebrows |

### Principles

- **Headlines:** `font-semibold`, negative letter-spacing (`tracking-tight`)
- **Body:** `text-ink-600`, relaxed line-height
- **Commerce copy:** Short, confident (Nike). Avoid long paragraphs above the fold.

---

## 3. Spacing System

4px base grid (Apple / Shopify standard).

| Token | Value | Tailwind |
|-------|-------|----------|
| xs | 4px | `1` |
| sm | 8px | `2` |
| md | 16px | `4` |
| lg | 24px | `6` |
| xl | 32px | `8` |
| 2xl | 48px | `12` |
| 3xl | 64px | `16` |

### Layout utilities

| Class | Purpose |
|-------|---------|
| `.ds-container` | Max 72rem, responsive padding |
| `.ds-container-narrow` | Max prose width (forms, checkout) |
| `.ds-section` | Vertical section rhythm (48–80px) |
| `.ds-section-tight` | Compact sections |

---

## 4. Buttons

| Class | Style |
|-------|--------|
| `.ds-btn-primary` | Black pill — main CTA (Nike / Apple Store) |
| `.ds-btn-accent` | Blue pill — secondary emphasis |
| `.ds-btn-secondary` | White + border — alternate actions |
| `.ds-btn-ghost` | Text-only — tertiary |
| `.ds-btn-danger` | Red — delete / cancel order |
| `.ds-btn-sm` / `.ds-btn-lg` | Size modifiers |
| `.ds-btn-icon` | Circular icon button |

**Example:**

```html
<button type="submit" class="ds-btn-primary ds-btn-lg">Place order</button>
<a href="/shop" class="ds-btn-secondary">Continue shopping</a>
```

**Interaction:** 250ms ease, subtle scale on press (`active:scale-[0.98]`), focus ring on keyboard nav.

---

## 5. Cards

| Class | Use |
|-------|-----|
| `.ds-card` | Default bordered card |
| `.ds-card-elevated` | Modals, featured blocks |
| `.ds-card-interactive` | Product grids, clickable tiles |
| `.ds-product-card` | Product listing preset |
| `.ds-card-header` / `.ds-card-body` / `.ds-card-footer` | Structured content |

**Example:**

```html
<article class="ds-product-card ds-hover-lift">
  <div class="ds-product-card-media group">
    <img class="h-full w-full object-cover ds-image-zoom" src="..." alt="">
  </div>
  <div class="ds-product-card-body">
    <h3 class="ds-heading-4">Basin Mixer</h3>
    <p class="ds-body-sm">Rs. 8,500</p>
  </div>
</article>
```

---

## 6. Forms

| Class | Element |
|-------|---------|
| `.ds-field` | Wrapper with spacing |
| `.ds-label` | Field label |
| `.ds-input` | Text input |
| `.ds-select` | Select |
| `.ds-textarea` | Textarea |
| `.ds-checkbox` / `.ds-radio` | Controls |
| `.ds-help` | Helper text |
| `.ds-error-text` | Validation message |
| `.ds-input-error` | Error state modifier |

Forms plugin strategy: `class` (apply `.ds-input` explicitly).

---

## 7. Badges

| Class | Use |
|-------|-----|
| `.ds-badge-neutral` | Default status |
| `.ds-badge-accent` | Featured / info |
| `.ds-badge-success` | Paid, delivered |
| `.ds-badge-warning` | Pending, low stock |
| `.ds-badge-danger` | Failed, cancelled |
| `.ds-badge-sale` | Discount |
| `.ds-badge-new` | New arrival |

---

## 8. Alerts

| Class | Use |
|-------|-----|
| `.ds-alert-success` | Order placed, saved |
| `.ds-alert-warning` | Stock warning |
| `.ds-alert-danger` | Payment failed |
| `.ds-alert-info` | Neutral notices |

```html
<div class="ds-alert-success" role="status">
  <p class="ds-alert-title">Order confirmed</p>
  <p>Your order #ORD-123 has been received.</p>
</div>
```

---

## 9. Loading Skeletons

| Class | Use |
|-------|-----|
| `.ds-skeleton-text` | Single line |
| `.ds-skeleton-title` | Heading placeholder |
| `.ds-skeleton-avatar` | User avatar |
| `.ds-skeleton-media` | Product image |
| `.ds-skeleton-card` | Full product card shell |

Shimmer animation: `animate-ds-shimmer` (1.6s loop).

---

## 10. Hover & Motion

| Utility | Effect |
|---------|--------|
| `.ds-hover-lift` | Translate Y -2px |
| `.ds-hover-scale` | Scale 1.02 |
| `.ds-hover-glow` | Accent shadow glow |
| `.ds-hover-fade` | Opacity 80% |
| `.ds-image-zoom` | Image scale inside `group` |
| `.ds-animate-in` | Fade in on mount |
| `.ds-animate-up` | Slide up + fade |

**Easing:** `ease-ds-out` — `cubic-bezier(0.22, 1, 0.36, 1)` (Apple-like deceleration)

**Duration:** 250ms default, 350ms for images and hero elements.

---

## File Structure

```
tailwind.config.js              # Theme tokens, animations, shadows
resources/css/
  app.css                         # Imports design system layers
  design-system/
    tokens.css                    # CSS custom properties
    components.css                # ds-* component classes
DESIGN_SYSTEM.md                  # This guide
```

---

## Adoption Guide (incremental)

1. Add `class="ds-root"` to `<body>` when ready (optional — tokens work via Tailwind alone).
2. Replace ad-hoc button classes with `.ds-btn-*`.
3. Wrap storefront sections in `.ds-container` + `.ds-section`.
4. Use `.ds-product-card` in product grids.
5. Apply `.ds-input` to checkout forms.
6. Use `.ds-alert-*` for flash messages instead of inline Tailwind.

No controller, service, or route changes required.

---

## Admin vs Storefront

| Area | Recommendation |
|------|----------------|
| **Storefront** | Full design system — black CTAs, generous whitespace |
| **Admin ERP** | Keep existing admin shell; use `.ds-table`, `.ds-card`, `.ds-badge-*` for consistency |

---

## Build

After editing tokens:

```bash
npm run dev
# or
npm run build
```
