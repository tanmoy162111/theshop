# Homepage Bold Redesign — Design Spec

**Date:** 2026-06-18
**App:** The Shop (Vue 3 + Vuetify 3 SPA storefront in `codecanyon-34858541-the-shop/install`, referred to as `$APP`)
**Branch base:** `main` (homepage "polish" pass already merged — commits `0562b7a`..`6c944b2`)
**Predecessor:** `docs/superpowers/specs/2026-06-17-homepage-redesign-design.md` (subtle polish). This spec goes **bolder** while keeping the same section set.

---

## Goal

Make the storefront homepage visually striking — not just polished — by restyling the **existing** sections with high visual impact: a cinematic hero with headline/CTA, punchier product cards, a trust strip, and stronger categories/headers. Stay theme-aware (drive all accents from `--primary` / `--soft-primary` / `--hov-primary`). No page restructuring beyond the one new trust strip; no reordering of product/shop sections.

**Why:** The earlier polish pass was too subtle — the page still reads as "the same homepage." The approved direction is "bold redesign of existing sections."

## Non-Goals (out of scope)

- No new product/shop/banner sections beyond the trust strip; no reordering existing ones.
- No changes to cart/checkout/product-detail/category/search pages (except the shared `ProductBox` style "one", scoped so other styles are untouched).
- No new database schema/migrations. Hero text uses new `Setting` rows (created on save), not new tables.
- Not fixing the empty "Medicines/Wellness/Personal Care" sections (admin **content** gaps, tracked separately).

---

## Architecture & Data Flow (existing, unchanged)

- Home sections render in `$APP/resources/js/pages/Home.vue`, composed of `components/home/*` components.
- Each section fetches from `Api/SettingController@home_setting($section)` (`$APP/app/Http/Controllers/Api/SettingController.php`), which reads `Setting` rows via the `get_setting($key, $default)` helper (`app/Http/Helpers.php:742`) and wraps each case in `Cache::remember('<key>', 86400, …)`.
- The hero ("sliders") case builds banner arrays from `home_slider_1..4_images` / `home_slider_1..4_links` via `banner_array_generate()`, cached under key `sliders`.
- Admin edits home settings through the existing Home Page settings controller/view (`HomeController` + `resources/views/backend/**`); saving writes `Setting` rows.
- **Cache rule:** after any settings change, `Cache::flush()` (the `sliders`/section caches are 86400s; the theme system already relies on this).
- **Theme-aware tokens:** `--primary`, `--soft-primary`, `--hov-primary` are injected in `resources/views/frontend/app.blade.php` from `base_color`; the polish pass added `--hp-surface`, `--hp-surface-muted`, `--hp-radius-card`, `--hp-shadow-sm`, `--hp-shadow-md`. Reuse these; add new tokens only if needed (e.g. a deeper primary shade for CTA shadows — derive with `color-mix` to stay theme-aware, never hardcode a brand hex).

---

## Components / Units of Work

### 1. Hero — `HomeSliders.vue` + new CMS fields

**Visual:** Convert the existing `v-row` (main `lg=6` banner + stacked side banners) into a cinematic hero:
- **Main banner (left, larger):** rounded framing (`--hp-radius-card`), `object-fit: cover`, with an absolutely-positioned **overlay** carrying: small eyebrow label (optional), **headline** (`home_hero_headline`), **subcopy** (`home_hero_subtext`), and a **CTA button** (`home_hero_cta_label` → `home_hero_cta_link`). Overlay uses a left-anchored dark gradient (`linear-gradient(90deg, rgba(0,0,0,.8), transparent)`) so text is legible over any banner image. CTA button background = `--primary`, hover = `--hov-primary`.
- **Side banners (right):** the existing 2–3 side slides become rounded "promo" cards with a bottom gradient + the banner's own link (existing data; no new fields).
- **Graceful empty:** if `home_hero_headline` is blank/unset, render the overlay-less framed hero (images only). The CTA renders only when both label and link are present.
- **RTL:** mirror the gradient direction and text alignment under `.v-locale--is-rtl`.
- **Reduced motion:** disable hover zoom via `@media (prefers-reduced-motion: reduce)`.

**New `Setting` keys (admin-editable):**
| key | type | purpose |
|-----|------|---------|
| `home_hero_headline` | text | hero headline |
| `home_hero_subtext` | text | hero subcopy |
| `home_hero_cta_label` | text | button label |
| `home_hero_cta_link` | text (URL/path) | button target |

**Backend:**
- Expose the four values in the API. Preferred: extend the existing `sliders` case payload with a `hero` object `{ headline, subtext, cta_label, cta_link }` (keeps one request), OR add a new `case 'hero'`. Either way the values come from `get_setting()` and are covered by the existing cache (must flush on save).
- Add the four fields to the existing Home Page settings admin form (same blade view + save path that already persists `home_slider_*`). Follow the existing `Setting` save pattern in that controller. After save, `Cache::flush()` (match how the page already invalidates `sliders`).

**Acceptance:** with headline/subtext/CTA set in admin, the hero shows overlaid text + working CTA over the main banner; clearing them yields a clean framed-images hero with no empty text boxes; colors follow the active theme; no overflow at 375/1440.

### 2. Product card — `ProductBox.vue` (style "one" only)

Builds on the polish pass (already has rounded depth, hover lift+zoom, pill badge, floating wishlist FAB). Make it **bolder**:
- Larger card paddings/typography; price more prominent (bigger `now` price, muted strikethrough `was`).
- **Full-width "Add to cart" button** at the card bottom that is `--hp-surface-muted` at rest and fills with `--primary` (text white) on card hover. Wraps the existing `showAddToCartDialog`/`buy_now` action — no logic change.
- **Star ratings:** render a 5-star row + `(reviews_count)` **only when `reviews_count > 0`**. `rating`/`reviews_count` are available on the product payload (`ProductCollection.php`). When zero (e.g. seeded demo products), render nothing (no fake stars, no empty space reserved).
- All scoped to `.product-box-one`; styles `two/three/four` untouched. RTL + reduced-motion guards retained.

**Acceptance:** style-one cards look bolder with prominent price, hover "Add to cart" fill, and real stars where reviews exist; category/search pages (other box styles) visually unchanged; reduced-motion disables hover transforms.

### 3. Trust strip — new row in `Home.vue`

A 4-item responsive row placed directly under the hero: **Free Delivery · Easy Returns · Secure Payments · Trusted Store** (icon + bold label + small subtext). Implemented as a small presentational component (e.g. `components/home/HomeTrustStrip.vue`) using **static translatable text** (i18n keys, e.g. `trust_free_delivery` with English fallbacks following the app's `$t()`-returns-key behavior — see Risks) and Line Awesome icons already bundled. Icon tint = `--primary`. **Not admin-editable** (scope decision). Reflows to 2×2 on mobile, hides nothing.

**Acceptance:** a clean 4-up trust row under the hero on desktop, 2×2 on mobile; text translatable; icons themed.

### 4. Categories + section headers

- `HomePopularCategories.vue`: strengthen the tile (builds on shipped `.hp-cat-*`) — slightly larger, clearer hover, consistent square imagery.
- Section headers: the `.section-title` accent-bar header already shipped; scale it up for the bold look (larger heading, clearer "View all"). Keep it in the shared `main.scss` polish block so all sections inherit it.

**Acceptance:** categories read as bold uniform cards; headers are larger with the primary accent bar and aligned "View all".

---

## Cross-Cutting

- **Theme-aware:** no brand hex literals; only the CSS variables / `color-mix` derivations. Verify by swapping industry theme at `/admin/theme` (e.g. Pharmacy `#0D9488`) and confirming hero CTA, card price/stars, trust icons, accent bars all recolor; then reset.
- **Build pipeline:** build on host — `cd $APP && npm run build` (container `theshop-app` has no node; `public/build` is git-ignored). App at `http://localhost:8000`.
- **Verify loop per task:** build → reload → screenshot at 1440 and 375 → check acceptance → 0 console errors.
- **i18n fallback:** this app's `$t('key')` returns the **key string** when a translation is missing (it is truthy), so `$t('x') || 'Default'` does **not** fall back. Use the computed-label pattern already used in `HomeAboutText.vue` (`(t && t !== 'key') ? t : 'Default'`) for any new user-facing strings (CTA default, trust labels).
- **RTL:** mirror directional values via logical properties or `.v-locale--is-rtl` overrides (hero gradient/text-align especially).
- **Accessibility:** CTA and "Add to cart" are real `<button>`/`<a>`; keep visible focus (Vuetify defaults). Hero overlay text must meet contrast over the gradient (gradient is strong enough at the text anchor).

## Risks / Mitigations

- **Hero text over arbitrary banner images** → strong left-anchored gradient guarantees legibility regardless of uploaded image.
- **Empty hero fields on existing installs** → graceful overlay-less fallback (no empty boxes).
- **Stale cache hiding new hero text** → flush cache on settings save (existing pattern); document in plan.
- **Faking ratings** → strictly gate stars on `reviews_count > 0`.
- **Shared `ProductBox` regressions** → all new CSS scoped to `.product-box-one`; explicit regression check on category/search pages.
- **Build breakage** → host build is the gate each task; `public/build` git-ignored so no broken bundle is committed.

## Files (anticipated)

- `$APP/resources/js/components/home/HomeSliders.vue` — hero restyle + overlay/CTA.
- `$APP/app/Http/Controllers/Api/SettingController.php` — expose hero text in API.
- Home Page settings admin controller + blade view (the one persisting `home_slider_*`) — add 4 hero fields + cache flush.
- `$APP/resources/js/components/product/ProductBox.vue` — bolder style-one card + conditional stars + add-to-cart.
- `$APP/resources/js/components/home/HomeTrustStrip.vue` — new component.
- `$APP/resources/js/pages/Home.vue` — mount trust strip under hero.
- `$APP/resources/js/components/home/HomePopularCategories.vue` — stronger tiles.
- `$APP/resources/sass/main.scss` — bump `.section-title` scale; any shared bold tokens.
- (optional) `$APP/resources/views/frontend/app.blade.php` — only if a new derived token is needed.

## Definition of Done

All four components restyled to the bold direction; hero text is admin-editable with graceful empty state; stars are real-data-gated; trust strip present and translatable; theme-swap recolors everything; clean build; responsive at 375/1440; no console errors; no regressions on category/search pages.
