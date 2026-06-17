# Homepage Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply high-impact, theme-aware visual polish to the storefront homepage (hero, product cards, category strip, section headers, about block, global rhythm) per the approved design spec.

**Architecture:** Pure presentation changes in a Vue 3 + Vuetify SPA. New styling lives in a clearly-marked block in `resources/sass/main.scss` (reusable tokens + classes) plus scoped component styles, all driven by the existing `var(--primary)` / `var(--soft-primary)` / `var(--hov-primary)` CSS variables so the look follows each client's industry theme. No new sections, no new CMS fields, no logic changes.

**Tech Stack:** Vue 3, Vuetify 3, SCSS, Vite 4, Swiper 11. App dir: `codecanyon-34858541-the-shop/install` (referred to below as `$APP`). Built assets in `$APP/public/build` (git-ignored). App served at `http://localhost:8000` (container `theshop-app`, which has **no node** — build on host).

**Spec:** `docs/superpowers/specs/2026-06-17-homepage-redesign-design.md`

---

## The Build + Verify Loop (use after every visual task)

There is no JS unit-test harness for styling. "Verification" for each task means:

```bash
cd codecanyon-34858541-the-shop/install
npm run build            # must end with "✓ built in …" and NO "error during build"
```

Then in the browser (Playwright MCP or manual): reload `http://localhost:8000/`, screenshot, and check the task's **Acceptance criteria**. Node deps are already installed (`node_modules` present). If a build ever produces a broken bundle, restore from the backup dir created in Task 1.

**Theme-aware rule (applies to every task):** never write a brand hex. Use `var(--primary)`, `var(--soft-primary)`, `var(--hov-primary)`, and the neutral tokens added in Task 2. RTL: mirror any directional value (`left/right`, `padding-left`) using logical properties or a `.v-locale--is-rtl` override.

---

## File Structure

- **Modify** `$APP/resources/sass/main.scss` — append one `/* === Homepage polish === */` block: tokens, `.section-title`, `.hp-surface*`, card/category/hero/about styles. Single source of truth for new CSS.
- **Modify** `$APP/resources/views/frontend/app.blade.php` — add neutral surface tokens to the `:root` style block; optional font swap.
- **Modify** `$APP/resources/js/pages/Home.vue` — wrap alternating sections in surface classes (already has the import fix from Task 1).
- **Modify** `$APP/resources/js/components/product/ProductBox.vue` — card style "one" restyle (scoped).
- **Modify** `$APP/resources/js/components/home/HomePopularCategories.vue` — category tiles.
- **Modify** `$APP/resources/js/components/home/HomeSliders.vue` — hero framing.
- **Modify** `$APP/resources/js/components/home/HomeAboutText.vue` — readable typography + Read-more.
- **Modify** each `$APP/resources/js/components/home/HomeProductSection*.vue` and `HomeShopSection*.vue` — swap bare `<h2>` for `.section-title`.

---

## Task 1: Unblock the build (case-sensitivity import fixes) + establish baseline

Linux is case-sensitive; three relative imports referenced wrong-cased filenames and broke `npm run build`. **These edits are already applied to the working tree** (done during planning) — this task verifies and commits them.

**Files:**
- Renamed: `$APP/resources/js/router/Home.js` → `home.js` (import was `./home`)
- Modified: `$APP/resources/js/pages/Home.vue:86` (`HomeBannerSectiontwo.vue` → `HomeBannerSectionTwo.vue`)
- Modified: `$APP/resources/js/components/product/AddToCart.vue:589` (`productgallery.vue` → `ProductGallery.vue`)

- [ ] **Step 1: Confirm no remaining case-mismatch imports**

```bash
cd codecanyon-34858541-the-shop/install
python3 - <<'PY'
import os, re, glob
root="resources/js"
imp=re.compile(r"""(?:from|import)\s+['"](\.[^'"]+)['"]""")
bad=0
for f in glob.glob(root+"/**/*.*", recursive=True):
    if not f.endswith((".js",".vue")): continue
    d=os.path.dirname(f); 
    for m in imp.finditer(open(f).read()):
        spec=m.group(1); base=os.path.normpath(os.path.join(d,spec))
        if any(os.path.exists(c) for c in (base,base+".js",base+".vue",os.path.join(base,"index.js"))): continue
        parent=os.path.dirname(base); name=os.path.basename(base).lower()
        if os.path.isdir(parent) and any(e.lower() in (name,name+".js",name+".vue") for e in os.listdir(parent)):
            print("MISMATCH",f,spec); bad+=1
print("bad=",bad)
PY
```
Expected: `bad= 0`

- [ ] **Step 2: Back up the live bundle, then build**

```bash
cp -r public/build /tmp/build-backup-pretask1
npm run build
```
Expected: ends with `✓ built in …`, no `error during build`.

- [ ] **Step 3: Verify the live site still renders**

Reload `http://localhost:8000/`, screenshot. Expected: homepage renders as before (header, hero image, Popular Categories, product grids, footer) — no blank page, no console module errors.

- [ ] **Step 4: Commit the build fix**

```bash
cd codecanyon-34858541-the-shop/install   # (run git from repo root)
git -C /home/tanmoy/Projects/Shop/theshop add \
  codecanyon-34858541-the-shop/install/resources/js/router/home.js \
  codecanyon-34858541-the-shop/install/resources/js/pages/Home.vue \
  codecanyon-34858541-the-shop/install/resources/js/components/product/AddToCart.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "fix(shop/build): correct case-sensitive Vue/router import paths

Linux build failed on ./home, HomeBannerSectiontwo.vue, productgallery.vue.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

**Acceptance criteria:** `npm run build` succeeds; homepage renders unchanged; fix committed.

---

## Task 2: Design tokens + global rhythm + (optional) typography

**Files:**
- Modify: `$APP/resources/views/frontend/app.blade.php` (`:root` style block)
- Modify: `$APP/resources/sass/main.scss` (append polish block)
- Modify: `$APP/resources/js/pages/Home.vue` (alternating surface wrappers)

- [ ] **Step 1: Add neutral + shape tokens to the `:root` block in `app.blade.php`**

Find the style block containing `--primary: {{ get_setting('base_color', '#e62d04') }};` and add directly below the `--soft-primary` line:

```css
            --hp-surface: #ffffff;
            --hp-surface-muted: #f7f8fa;
            --hp-radius-card: 14px;
            --hp-shadow-sm: 0 1px 3px rgba(16,24,40,.06), 0 1px 2px rgba(16,24,40,.04);
            --hp-shadow-md: 0 10px 24px rgba(16,24,40,.10), 0 4px 8px rgba(16,24,40,.06);
```

- [ ] **Step 2: (Optional) Typography upgrade — Rubik + Nunito Sans**

In `app.blade.php`, replace the existing Roboto `<link>` (the `fonts.googleapis.com/css2?family=Roboto...` line) with:

```html
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
```

Then in the polish block in `main.scss` (Step 3) the body/heading font rules below will pick them up. **If the user prefers to keep Roboto, skip this step and the two `font-family` lines in Step 3.**

- [ ] **Step 3: Append the polish block to `main.scss`**

Append at end of `$APP/resources/sass/main.scss`:

```scss
/* === Homepage polish (theme-aware) === */
body { font-family: "Nunito Sans", var(--font-family-sans-serif); }            /* skip if keeping Roboto */
h1, h2, h3, .section-title__text { font-family: "Rubik", var(--font-family-sans-serif); } /* skip if keeping Roboto */

.hp-surface        { background: var(--hp-surface); }
.hp-surface-muted  { background: var(--hp-surface-muted); }
.hp-section-pad    { padding-top: 32px; padding-bottom: 32px; }
@media (min-width: 960px) { .hp-section-pad { padding-top: 48px; padding-bottom: 48px; } }

/* Reusable section header */
.section-title {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px; gap: 12px;
}
.section-title__text {
  display: flex; align-items: center; gap: 12px;
  font-size: 20px; font-weight: 700; line-height: 1.2; margin: 0;
}
.section-title__text::before {
  content: ""; display: inline-block; width: 4px; height: 22px;
  border-radius: 4px; background: var(--primary); flex: 0 0 auto;
}
@media (min-width: 960px) { .section-title__text { font-size: 26px; } }
.section-title__link { color: var(--primary); font-size: 14px; white-space: nowrap; }
.section-title__link:hover { color: var(--hov-primary); }

@media (prefers-reduced-motion: reduce) {
  * { transition: none !important; animation: none !important; }
}
```

- [ ] **Step 4: Apply alternating surfaces in `Home.vue`**

Read `$APP/resources/js/pages/Home.vue`. Wrap roughly every other product/banner group in a surface so sections separate visually. Minimal version — wrap the existing bottom grey block and add muted backgrounds to alternating sections. Replace the existing bottom block:

```html
    <div class="py-8 bg-grey-lighten-4 mt-8">
```
with:
```html
    <div class="hp-surface-muted hp-section-pad mt-8">
```
And wrap `<HomeProductSectionTwo />` and `<HomeProductSectionFour />` each in:
```html
    <div class="hp-surface-muted hp-section-pad"> <HomeProductSectionTwo /> </div>
```
(Leave odd sections on the default white background.)

- [ ] **Step 5: Build + verify**

Run the Build + Verify Loop. **Acceptance criteria:** build succeeds; headings render in Rubik (if enabled); alternating sections show a subtle grey/white banding; no layout breakage at 1440 and 375 widths.

- [ ] **Step 6: Commit**

```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/sass/main.scss codecanyon-34858541-the-shop/install/resources/views/frontend/app.blade.php codecanyon-34858541-the-shop/install/resources/js/pages/Home.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): design tokens, section rhythm, typography

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 3: Reusable section headers

Replace bare `<h2>{{ title }}</h2>` (and `<h2>{{ $t('popular_categories') }}</h2>` style headers) with the `.section-title` markup in every home section that has one.

**Files (modify each):** `HomeProductSectionOne.vue`, `HomeProductSectionTwo.vue`, `HomeProductSectionThree.vue`, `HomeProductSectionFour.vue`, `HomeProductSectionFive.vue`, `HomeProductSectionSix.vue`, `HomeShopSectionOne.vue` … `HomeShopSectionFive.vue` (all under `$APP/resources/js/components/home/`).

- [ ] **Step 1: In each `HomeProductSection*.vue`, replace the header**

Read the file. Replace:
```html
      <h2 class="mb-4">{{ title }}</h2>
```
with:
```html
      <div class="section-title">
        <h2 class="section-title__text">{{ title }}</h2>
      </div>
```
Also remove the now-redundant scoped `<style>` `h2 { font-size … }` overrides at the bottom of those files (the `.section-title__text` rule handles sizing). Leave everything else untouched.

- [ ] **Step 2: For shop sections with a "View all"**, use the full header form where a target route exists:
```html
      <div class="section-title">
        <h2 class="section-title__text">{{ title }}</h2>
        <router-link class="section-title__link" :to="{ name: 'AllShops' }">{{ $t('view_all') }} <i class="las la-angle-right"></i></router-link>
      </div>
```
(Only add the link if the component already had one or a valid route name exists; otherwise omit.)

- [ ] **Step 3: Build + verify.** **Acceptance criteria:** every section row shows the new title with a primary-colored accent bar; consistent spacing; "View all" aligned right where present.

- [ ] **Step 4: Commit**
```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/js/components/home/
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): apply reusable section-title headers

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 4: Product card restyle (`ProductBox.vue`, style "one")

`ProductBox.vue` is shared by category/search/shop pages. **Scope all new styling to `.product-box-one`** so other `boxStyle` variants are untouched.

**Files:** Modify `$APP/resources/js/components/product/ProductBox.vue` (add a scoped `<style>` block; minor template tweak for the wishlist position).

- [ ] **Step 1: Add a scoped style block to `ProductBox.vue`**

Append before the closing of the component (after `</script>`), or extend an existing style block:

```html
<style scoped>
.product-box-one .rounded.border {
  border-radius: var(--hp-radius-card) !important;
  box-shadow: var(--hp-shadow-sm);
  transition: transform .2s ease-out, box-shadow .2s ease-out;
  background: var(--hp-surface);
  height: 100%;
}
.product-box-one .rounded.border:hover {
  transform: translateY(-4px);
  box-shadow: var(--hp-shadow-md);
}
.product-box-one .position-relative { overflow: hidden; }
.product-box-one img.h-180px {
  transition: transform .3s ease-out;
}
.product-box-one .rounded.border:hover img.h-180px { transform: scale(1.05); }
.product-box-one .discount-badge {
  background: var(--danger); color:#fff; border-radius: 999px;
  padding: 2px 10px; font-weight: 700; font-size: 12px;
}
/* full-width Buy now */
.product-box-one button .la-shopping-cart { color: var(--primary); }
.product-box-one .fw-700.fs-13 { color: var(--primary); }
@media (prefers-reduced-motion: reduce) {
  .product-box-one .rounded.border, .product-box-one img.h-180px { transition: none; }
  .product-box-one .rounded.border:hover { transform: none; }
  .product-box-one .rounded.border:hover img.h-180px { transform: none; }
}
</style>
```

- [ ] **Step 2: Float the wishlist heart onto the image (style "one" only)**

Read the template. The wishlist button lives in the action row. For `boxStyle == 'one'`, add a floating copy on the image corner by inserting inside the `<div class="position-relative">` (after the `discount-badge` div), guarded so it only renders for style one:

```html
            <button
              v-if="boxStyle == 'one'"
              class="text-primary pa-1 lh-1 hp-wishlist-fab"
              type="button"
              :aria-label="$t('add_to_wishlist') || 'Add to wishlist'"
              @click.prevent="isThisWishlisted(productDetails.id) ? removeFromWishlist(productDetails.id) : addNewWishlist(productDetails.id)"
            >
              <i :class="['ts-02 fs-18', isThisWishlisted(productDetails.id) ? 'la la-heart' : 'la la-heart-o']"></i>
            </button>
```
And add to the scoped style:
```css
.product-box-one .hp-wishlist-fab {
  position: absolute; top: 8px; right: 8px; z-index: 2;
  background: rgba(255,255,255,.9); border-radius: 999px;
  width: 36px; height: 36px; display:flex; align-items:center; justify-content:center;
  box-shadow: var(--hp-shadow-sm); cursor: pointer;
}
.v-locale--is-rtl .product-box-one .hp-wishlist-fab { right:auto; left: 8px; }
```
(Leave the existing in-row wishlist button as-is for other styles; for style one it's acceptable to keep both, or hide the in-row one via `v-if="boxStyle != 'one'"` — prefer hiding the in-row heart for style one to avoid duplication.)

- [ ] **Step 3: Build + verify.** **Acceptance criteria:** homepage product cards have rounded corners, lift + image-zoom on hover, pill red discount badge, primary-colored price, floating wishlist heart top-right that toggles. Hover lift disabled under reduced-motion.

- [ ] **Step 4: Regression check on shared usage**

Visit a category page and a search results page; confirm `boxStyle` two/three/four cards are visually unchanged.
```
http://localhost:8000/category/<any-slug>   (use a slug visible from Popular Categories)
```

- [ ] **Step 5: Commit**
```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/js/components/product/ProductBox.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): restyle product card (style one) with depth, hover, floating wishlist

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 5: Category strip (`HomePopularCategories.vue`)

**Files:** Modify `$APP/resources/js/components/home/HomePopularCategories.vue`.

- [ ] **Step 1: Restyle the category tile**

Read the file. Replace the tile `<router-link>` class and add a fixed image box:
```html
            <router-link
              class="hp-cat-tile text-reset d-block text-center"
              :to="{ name: 'Category', params: {categorySlug: category.slug}}"
            >
              <div class="hp-cat-img">
                <img :src="category.banner" :alt="category.name" @error="imageFallback($event)">
              </div>
              <div class="hp-cat-name">{{ category.name }}</div>
            </router-link>
```

- [ ] **Step 2: Add scoped styles**
```html
<style scoped>
.hp-cat-tile {
  border-radius: var(--hp-radius-card); background: var(--hp-surface-muted);
  padding: 12px; transition: transform .2s ease-out, box-shadow .2s ease-out;
}
.hp-cat-tile:hover { transform: translateY(-3px); box-shadow: var(--hp-shadow-md); }
.hp-cat-img { aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background:#fff; }
.hp-cat-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease-out; }
.hp-cat-tile:hover .hp-cat-img img { transform: scale(1.06); }
.hp-cat-name { margin-top: 10px; font-size: 13px; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (prefers-reduced-motion: reduce) {
  .hp-cat-tile, .hp-cat-img img { transition: none; }
  .hp-cat-tile:hover { transform: none; } .hp-cat-tile:hover .hp-cat-img img { transform: none; }
}
</style>
```

- [ ] **Step 3: Build + verify.** **Acceptance criteria:** uniform square category tiles with muted background, hover lift + image zoom, single-line truncated labels.

- [ ] **Step 4: Commit**
```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/js/components/home/HomePopularCategories.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): redesign popular-categories tiles

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 6: Hero framing (`HomeSliders.vue`)

**Files:** Modify `$APP/resources/js/components/home/HomeSliders.vue`.

- [ ] **Step 1: Read the file fully** to learn the actual slide/banner markup and Swiper config (the loader markup uses `v-row` with `lg=6` main slide + side banners).

- [ ] **Step 2: Add rounded framing + hover zoom via a scoped style block**

Add scoped styles targeting the slider images/columns (adjust selectors to match the real markup found in Step 1):
```html
<style scoped>
.hp-hero :deep(img) {
  border-radius: var(--hp-radius-card);
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .35s ease-out;
}
.hp-hero :deep(.lh-0) { overflow: hidden; border-radius: var(--hp-radius-card); }
.hp-hero :deep(a:hover img) { transform: scale(1.03); }
.hp-hero :deep(.swiper-pagination-bullet-active) { background: var(--primary); }
.hp-hero :deep(.swiper-button-next), .hp-hero :deep(.swiper-button-prev) { color: var(--primary); }
@media (prefers-reduced-motion: reduce) {
  .hp-hero :deep(img) { transition: none; } .hp-hero :deep(a:hover img) { transform: none; }
}
</style>
```

- [ ] **Step 3: Add the `hp-hero` class** to the component's outermost wrapper `<div class="mb-5">` → `<div class="mb-5 hp-hero">`.

- [ ] **Step 4: Build + verify.** **Acceptance criteria:** hero images have rounded corners and consistent fill, gentle zoom on hover, pagination/arrows tinted primary; no overflow/clipping issues; images don't distort (object-fit working). Verify at 375 and 1440.

- [ ] **Step 5: Commit**
```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/js/components/home/HomeSliders.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): rounded hero framing with hover zoom and themed controls

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 7: About block (`HomeAboutText.vue`) — readable + collapsible

**Files:** Modify `$APP/resources/js/components/home/HomeAboutText.vue`.

- [ ] **Step 1: Wrap the rendered HTML, add a Read-more toggle**

Replace the template:
```html
<template>
    <div v-if="!loading" class="hp-about">
        <div :class="['hp-about__body', { 'hp-about__body--collapsed': !expanded }]" v-html="data"></div>
        <button class="hp-about__toggle" type="button" @click="expanded = !expanded">
            {{ expanded ? $t('show_less') || 'Show less' : $t('read_more') || 'Read more' }}
        </button>
    </div>
</template>
```
And add `expanded: false` to `data()`:
```js
    data: () => ({
        loading: true,
        data: null,
        expanded: false,
    }),
```

- [ ] **Step 2: Add scoped styles**
```html
<style scoped>
.hp-about { max-width: 72ch; margin: 0 auto; background: var(--hp-surface);
  border-radius: var(--hp-radius-card); box-shadow: var(--hp-shadow-sm); padding: 24px; }
.hp-about__body :deep(*) { line-height: 1.7; }
.hp-about__body :deep(h1), .hp-about__body :deep(h2), .hp-about__body :deep(h3) {
  margin: 1.2em 0 .5em; line-height: 1.3; }
.hp-about__body :deep(p) { margin: 0 0 1em; }
.hp-about__body--collapsed { max-height: 160px; overflow: hidden;
  -webkit-mask-image: linear-gradient(180deg,#000 60%,transparent); mask-image: linear-gradient(180deg,#000 60%,transparent); }
.hp-about__toggle { margin-top: 12px; color: var(--primary); font-weight: 600; cursor: pointer; }
.hp-about__toggle:hover { color: var(--hov-primary); }
</style>
```

- [ ] **Step 3: Build + verify.** **Acceptance criteria:** the former grey wall is now a centered, readable, max-width card; collapsed by default with a fade + "Read more"; expands on click; no longer dominates the page.

- [ ] **Step 4: Commit**
```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/js/components/home/HomeAboutText.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): readable, collapsible about block

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 8: Cross-cutting verification

- [ ] **Step 1: Responsive** — screenshot the homepage at widths 375, 768, 1024, 1440. No horizontal scroll; cards reflow; hero readable.

- [ ] **Step 2: Theme swap** — in admin, switch to a different industry theme (different `base_color`) at `http://localhost:8000/admin/theme`, reload home. Confirm accent bar, price color, badges, hero controls, links all follow the new primary. Switch back.

- [ ] **Step 3: Reduced motion** — emulate `prefers-reduced-motion: reduce`; confirm hover lifts/zooms are disabled.

- [ ] **Step 4: Focus states** — keyboard-tab through hero, category tiles, product card buttons; confirm visible focus rings (Vuetify defaults retained).

- [ ] **Step 5: Final before/after** — full-page screenshot; compare against `current-home.jpeg` baseline. Save as evidence.

- [ ] **Step 6: Final build is clean** — `npm run build` ends with `✓ built`, no errors.

**Acceptance criteria:** all of the above pass; no regressions on category/search pages from Task 4.

---

## Self-Review Notes (author)

- **Spec coverage:** Hero→T6, Product card→T4, Category strip→T5, Section headers→T3, About block→T7, Global rhythm/typography/tokens→T2, theme-aware (var tokens)→every task, build risk→T1+loop, ProductBox shared-use risk→T4 Step 4, RTL→T4/T5 overrides, theme-swap verify→T8. All spec sections mapped.
- **Out of scope** preserved: no new sections, no CMS fields, no reordering.
- **Known empty sections** ("Medicines/Wellness/Personal Care" render empty with a broken image) are **admin content gaps, not styling** — out of scope here; flag to user separately.
