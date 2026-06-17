# Store Industry Themes — Design Spec

**Date:** 2026-06-17
**Status:** Approved (pending spec review)
**App:** The Shop (`codecanyon-34858541-the-shop/install/`, Laravel 9, Vue 3 SPA storefront)
**Scope owner:** Shop admin only (v1)

## Goal

Let a store owner switch their storefront between ready-made **industry themes**
(Electronics, Supershop/Grocery, Pharmacy, Pet Shop) from the shop admin panel.
Applying a theme changes the **look** (primary color, home layout, banners) and,
optionally, **seeds a demo catalog** for that vertical. Theme-seeded content is
fully tracked and reversible so it never trashes the merchant's real data.

This is part of the broader SaaS direction ("industry themes" pillar). It is a
standalone feature with no dependency on the control-plane/agent work.

## Decisions (locked)

| Decision | Choice |
|---|---|
| Theme scope | Look **+ demo content** (color/layout + demo categories & sample products) |
| Control point | **Shop admin only** (central super-admin push deferred) |
| Demo data safety | **Tagged + opt-in**: look always applies; demo catalog is a separate checkbox; theme-seeded items are tracked and removed on switch/reset; merchant data never touched |
| Verticals (v1) | Electronics, Supershop/Grocery, Pharmacy, Pet Shop |
| Banner assets | **Generated SVG/gradient banners** (per-vertical color + title/tagline), shipped as real files; no stock photos |
| Architecture | Settings-overlay + tracking table; **no changes to core CodeCanyon tables** |

## Why this is low-risk

The Shop's storefront is a Vue 3 SPA whose entire look is already data-driven
from the `settings` table:
- `base_color` (read by the SPA as `shopSetting.primaryColor`)
- `header_logo`, `footer_logo`
- `home_slider_*_img`, `home_banner_*_img` (store **upload IDs** referencing the `uploads` table)
- `home_product_section_*_title`, `home_product_section_*_products`
- `home_popular_categories`

A theme is therefore a **curated bundle of values written into the existing
`settings` table** plus optional tagged catalog rows. No compiled SPA changes
and no vendor-table schema changes are required.

## Architecture

### Components

| Component | Responsibility |
|---|---|
| `App\Themes\ThemePreset` (abstract) + 4 concrete presets (`ElectronicsPreset`, `SupershopPreset`, `PharmacyPreset`, `PetShopPreset`) | Declare the vertical's `base_color`, home section titles, banner/slider gradient+text specs, and a demo-catalog manifest (category tree + sample products) |
| `App\Themes\BannerGenerator` | Render each preset's SVG banner/slider images, write files under `public/uploads/themes/`, register rows in `uploads`, return upload IDs |
| `App\Themes\ThemeApplier` | Transactionally apply/reset a theme: roll back previous → generate banners → write `settings` → (opt-in) seed demo catalog → record everything in tracking tables; clear settings cache |
| `theme_applications` table | One row per active application: `vertical`, `demo_loaded`, `applied_at` |
| `theme_application_items` table | One row per tracked artifact created/overwritten by an application |
| Admin **Store Theme** page (`resources/views/backend/theme/index.blade.php`) + `App\Http\Controllers\Admin\ThemeController` | 4 vertical cards (color swatch + mini preview), "Also load sample catalog" checkbox, **Apply**, and **Reset to default look / Remove demo data** |

### New tables (the only schema changes)

`theme_applications`
- `id`
- `vertical` (string: electronics | supershop | pharmacy | pet_shop)
- `demo_loaded` (boolean)
- `applied_at` (timestamp)
- `timestamps`

`theme_application_items`
- `id`
- `theme_application_id` (FK → theme_applications, cascade delete)
- `kind` (enum: `setting` | `upload` | `category` | `product`)
- `ref_id` (nullable int — upload/category/product id)
- `setting_type` (nullable string — the `settings.type` key, when kind=setting)
- `prior_value` (nullable text — previous setting value for restore; null = the key did not exist before)
- `timestamps`

There is at most one active `theme_applications` row at a time (reset/switch
deletes the prior one).

## Data flow

### Apply — `POST /admin/theme/apply { vertical, load_demo }`

```
ThemeApplier::apply(vertical, loadDemo):
  DB transaction:
    1. rollback(previousApplication)  // no-op if none; see Reset, minus its own commit
    2. preset = ThemePreset::for(vertical)
    3. banners = BannerGenerator::generate(preset)
         for each slot: write SVG file → insert uploads row → collect {upload_id}
    4. for each look setting (base_color, home_slider_*_img, home_banner_*_img,
       home_product_section_*_title, home_popular_categories, optional logos):
         capture prior (settings.type, value) into items (prior_value, kind=setting)
         upsert settings row with the preset/banner value
    5. if loadDemo:
         create category tree from preset manifest (parent → children),
           attach generated category banner/icon upload IDs
         create sample products (shop_id = admin shop id, published = 1,
           main_category = seeded category, price/name from manifest)
         record every new category_id / product_id / upload_id into items
    6. insert theme_applications {vertical, demo_loaded: loadDemo, applied_at: now}
       insert all theme_application_items
  commit
  clear settings cache (get_setting cache in app/Http/Helpers.php)
```

The SPA reads the new values on its next config load. No rebuild.

### Reset / switch — `POST /admin/theme/reset` (also step 1 of every apply)

```
ThemeApplier::rollback(application):  // skip if no active application
  DB transaction:
    for each item by kind:
      setting  → restore prior_value, or delete the row if prior_value is null
      upload   → soft-delete uploads row + delete the generated file
      category → delete (only these tracked ids)
      product  → delete (only these tracked ids)
    delete theme_applications row (cascade deletes items)
  commit
  clear settings cache
```

### Safety guarantee
- Reversal walks **only tracked item IDs**; merchant-created settings/categories/products are never in that set, so they are never touched.
- Switching Pharmacy → Electronics = `rollback(Pharmacy)` then `apply(Electronics)`, atomically.
- `load_demo` unchecked → steps 3–4 only (look); step 5 skipped; reset still restores the prior look cleanly.
- Re-applying the same theme is idempotent: the prior application is rolled back first, so no duplicate demo rows or orphan uploads.

### Edge handling
- **No active theme yet** → rollback is a no-op; apply records the original setting values as `prior_value`, so the first reset returns the store to its pre-theme look.
- **Banner generation or catalog insert fails** → the whole transaction rolls back; the store stays on its current theme (fail-safe; nothing half-applied).
- **Single-vendor visibility** → demo products are seeded with `shop_id` = admin's shop so they appear on the storefront (per the `published_shops_ids()` / `admin.shop_id` constraint in the install setup).

## Preset content (v1)

| Vertical | `base_color` | Home sections | Demo category tree (sample) | Tagline |
|---|---|---|---|---|
| Electronics | `#2563EB` | Featured · Best Sellers · New Arrivals | Phones › (Smartphones, Accessories); Computers › (Laptops, Monitors); Audio › (Headphones) | "Tech that moves you" |
| Supershop / Grocery | `#16A34A` | Daily Deals · Groceries · Household | Fruits & Vegetables; Beverages; Snacks; Household › (Cleaning, Paper) | "Fresh, every day" |
| Pharmacy | `#0D9488` | Medicines · Wellness · Personal Care | Medicines › (OTC, Prescription); Vitamins & Supplements; Personal Care; Baby Care | "Your health, delivered" |
| Pet Shop | `#D97706` | Dogs · Cats · Pet Food & Accessories | Dogs › (Food, Toys); Cats › (Food, Litter); Birds; Aquarium | "Everything your pet loves" |

Each preset seeds ~2–4 sample published products per leaf category (name, price,
generated banner as image). Generated gradient banners carry the vertical color
plus the section/tagline text.

## Admin UI

- New backend route group (admin-only, **exempt from `agent.enforce` like the agent settings page** so it stays reachable):
  - `GET  /admin/theme` → `ThemeController@index` (name `theme.index`)
  - `POST /admin/theme/apply` → `ThemeController@apply` (name `theme.apply`)
  - `POST /admin/theme/reset` → `ThemeController@reset` (name `theme.reset`)
- Page: four vertical cards, each with the `base_color` swatch and a small layout
  preview; an "Also load sample catalog" checkbox; an **Apply** button; and a
  **Reset to default look / Remove demo data** action. Shows the currently active
  theme (from `theme_applications`).
- Menu entry under the admin "Appearance"/"Website Setup" section.

## Testing strategy

Reuse the existing isolated-SQLite harness pattern (as the agent tests do) so
theme tests never touch the live store DB. A `Tests\Theme\ThemeTestCase` builds
the minimal schema the applier needs: `settings`, `uploads`, `categories`,
`products`, plus `theme_applications` and `theme_application_items`.

| Test | Asserts |
|---|---|
| apply writes look settings | `base_color`, section titles, banner upload IDs land in `settings` for the chosen vertical |
| apply with load_demo seeds tagged catalog | demo categories + products created; every ID recorded in `theme_application_items` |
| apply without load_demo skips catalog | settings written; zero categories/products created |
| reset restores prior look | a pre-existing `base_color` is captured and restored on reset; theme-seeded items deleted |
| reset leaves merchant data untouched | a non-tracked product/category/setting survives reset |
| switching themes is clean | Pharmacy→Electronics leaves only Electronics' tagged items; no remnants, no duplicates |
| re-apply same theme is idempotent | no duplicate demo rows or orphan uploads |
| transaction rolls back on seed failure | forced mid-apply failure leaves the store on its current theme |
| BannerGenerator | produces a valid image file + an `uploads` row per slot |

## Out of scope (v1 / future)

- Central super-admin assigning/pushing an industry per client (deferred to the SaaS control-plane direction).
- Per-vertical font pairings or full CSS-framework swaps (only `base_color` + layout config in v1).
- Stock/photographic banner imagery.
- Additional verticals beyond the four (each is just a new `ThemePreset` subclass).

## Files (anticipated)

New:
- `app/Themes/ThemePreset.php` (+ `ElectronicsPreset`, `SupershopPreset`, `PharmacyPreset`, `PetShopPreset`)
- `app/Themes/BannerGenerator.php`
- `app/Themes/ThemeApplier.php`
- `app/Http/Controllers/Admin/ThemeController.php`
- `database/migrations/*_create_theme_applications_table.php`
- `database/migrations/*_create_theme_application_items_table.php`
- `resources/views/backend/theme/index.blade.php`
- `tests/Theme/ThemeTestCase.php` (+ feature/unit tests above)

Modified:
- `routes/admin.php` (theme route group)
- admin sidebar menu partial (Appearance section link)
- `phpunit.xml` already points tests at the sqlite_testing connection (from agent work)
