# Homepage Redesign — High-Impact Polish (Direction A: Clean Modern Retail)

**Date:** 2026-06-17
**Status:** Approved (design), pending implementation plan
**Scope:** High-impact visual polish of the storefront homepage. Theme-aware. No new sections, no re-ordering, no new CMS fields, no header/footer/vendor-logic changes.

## Context

The storefront is a **Vue 3 + Vuetify SPA** (`codecanyon-34858541-the-shop/install`). The homepage (`resources/js/pages/Home.vue`) stacks ~20 admin-driven sections. The theme color is injected as CSS variables in `resources/views/frontend/app.blade.php`:

```css
--primary:      {{ get_setting('base_color', '#e62d04') }};
--soft-primary: rgba(base_color, 0.15);
--hov-primary:  #e56f0e; /* existing */
```

The industry-theme `app/Themes/ThemeApplier.php` swaps `base_color` per client, so **theme-awareness means styling everything with `var(--primary)` / `var(--soft-primary)` / `var(--hov-primary)`** — never hardcoded brand hex.

Assets are built with **Vite**; changes to `.vue`/`.scss` require a rebuild inside the `theshop-app` container before they show at `localhost:8000`.

### Current problems (from baseline screenshot)
- Weak hero: small static banner grid, no framing.
- Plain product cards, low visual hierarchy.
- Tiny, dated category thumbnails.
- Bare `<h2>` section headers, inconsistent rhythm.
- A large unstyled grey wall of admin text (`HomeAboutText`) dominates the lower page.
- Generic, dated overall feel.

## Design Principles (from ui-ux-pro-max, e-commerce/clean-modern)

- **Depth, not flat:** subtle shadows + hover elevation; consistent elevation scale.
- **Motion:** 150–300ms transitions, `transform`/`opacity` only, `ease-out` on enter; respect `prefers-reduced-motion`.
- **Hover affordance:** color shift + lift on interactive cards; `cursor-pointer` everywhere clickable.
- **Rhythm & whitespace:** consistent vertical spacing tiers (16/24/32/48), generous section gaps, container max-width.
- **Hierarchy via size/weight/spacing**, not color alone.
- **Accessibility:** 4.5:1 text contrast, visible focus rings, alt text preserved, no emoji icons (keep existing Line Awesome icon font).
- **Responsive:** verify at 375 / 768 / 1024 / 1440.
- **Kill anti-patterns:** the text-heavy grey wall; flat-without-depth cards.

## Theming Tokens

Reuse and extend the existing CSS-variable contract. Add a small set of derived tokens in `app.blade.php`'s style block (so they stay theme-driven) and/or `main.scss`:

- `--primary`, `--soft-primary`, `--hov-primary` — already present, used for accents/CTAs.
- Neutral surface tokens for section alternation: `--surface` (white) and `--surface-muted` (very light grey, e.g. `#f7f8fa`).
- A consistent radius token (e.g. `--radius-card: 14px`) and shadow scale (`--shadow-sm`, `--shadow-md`) defined in `main.scss`.

All new styling lives in a clearly marked block in `main.scss` plus scoped component styles, using these tokens.

## Components & Changes

### 1. Hero — `components/home/HomeSliders.vue`
Restyle the existing slider + banner grid; **do not invent copy or CMS fields** (multi-client safe).
- Larger hero with `--radius-card` rounded corners and `--shadow-md`.
- Consistent slide heights; images use `object-fit: cover`; declared aspect-ratio to avoid CLS.
- Subtle gradient scrim on slides only where overlaid text/controls need legibility.
- Refined arrows + pagination dots tinted with `var(--primary)`.
- Gentle image zoom (`transform: scale(1.03)`, 200–300ms) on hover; disabled under reduced-motion.

### 2. Product card — `components/product/ProductBox.vue` (style `"one"`)
- Card: `--radius-card`, 1px `--border-color`, `--shadow-sm`; on hover lift (`translateY(-4px)`) + `--shadow-md` + `cursor-pointer`.
- Image: fixed aspect ratio, `object-fit: cover`, zoom on hover (transform only).
- Discount badge: pill in red `--danger` (standard retail sale-emphasis convention), top-left.
- Wishlist heart: floating icon button on the image top-right corner with a translucent backdrop; ≥40px hit area; keeps existing wishlist actions/state.
- Price/title hierarchy: bold price in `var(--primary)`; struck-through original muted; 2-line clamped title.
- "Buy now": full-width `btn-soft-primary` style; hover → `var(--primary)` fill.
- Preserve all existing behavior: club points, compare, out-of-stock, add-to-cart dialog, i18n strings.

### 3. Category strip — `components/home/HomePopularCategories.vue`
- Replace tiny bordered thumbnails with uniform rounded tiles: consistent image box (square, `object-fit: cover`), `--radius-card`, soft `--surface-muted` background, hover lift.
- Clearer centered label (truncate to one line).
- Keep "View all" link (tinted `var(--primary)`).

### 4. Section headers — all `HomeProductSection*` and `HomeShopSection*`
- Introduce one reusable `.section-title` header treatment in `main.scss`, applied in each section's template (a CSS class rather than a new component — less churn, fits the polish scope):
  - Bold title (Rubik), short accent bar in `var(--primary)` to the left, generous top/bottom spacing.
  - Optional right-aligned "View all" link where a target route exists.
- Replaces the bare `<h2>` + ad-hoc font-size overrides.

### 5. About block — `components/home/HomeAboutText.vue`
Renders admin HTML from the `home_about_text` setting; must not alter the stored content.
- Constrain to a readable max-width (~70ch) centered.
- Apply typographic rhythm: line-height 1.6–1.75, heading sizes, paragraph spacing (scoped `:deep()` styles on the `v-html` container).
- Place on a tidy `--surface-muted` card.
- Collapse behind a "Read more" toggle (show ~first portion, expand on click) so it stops dominating the page. Toggle respects reduced-motion (instant or quick height transition).

### 6. Global rhythm & typography — `main.scss`, `app.blade.php`, `Home.vue`
- Consistent vertical rhythm between sections; sensible container max-width.
- Alternate section backgrounds (`--surface` / `--surface-muted`) so sections separate visually.
- **Typography upgrade:** swap the stock Roboto link for **Rubik** (headings) + **Nunito Sans** (body) with `display=swap`; wire into the font-family stack. (High-impact, low-risk; can be toggled off if the user prefers to keep Roboto.)
- Reusable utility classes (`.section-title`, card hover, surface helpers) to keep it DRY.

## Out of Scope
New sections, section re-ordering, new CMS fields, vendor-section logic, header/footer redesign, cart/checkout, admin panel.

## Risks & Constraints
- **Build step:** must rebuild Vite assets in the container; verify the build command and that the running app serves rebuilt assets (not a stale cached bundle).
- **Shared component reuse:** `ProductBox.vue` is used beyond the homepage (category/search/shop pages). Card restyle must be verified to not break those contexts — favor changes scoped to `boxStyle == 'one'`.
- **Theme swap:** verify the polished look holds across at least two industry themes (different `base_color`).
- **RTL:** project supports RTL (`v-locale--is-rtl`); new directional styles (badge position, accent bar) must have RTL equivalents.
- **Admin HTML:** `HomeAboutText` content is arbitrary; the readable-typography styles must degrade gracefully for unknown markup.

## Verification
1. Rebuild assets in `theshop-app`; reload `localhost:8000`.
2. Full-page screenshot; compare against baseline (`current-home.jpeg`).
3. Check responsive at 375 / 768 / 1024 / 1440.
4. Switch one industry theme (different `base_color`) and confirm accents follow.
5. Spot-check `ProductBox` on a category/search page for regressions.
6. Confirm focus rings, hover states, and reduced-motion behavior.
