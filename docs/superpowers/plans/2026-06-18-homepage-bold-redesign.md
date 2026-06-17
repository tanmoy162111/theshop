# Homepage Bold Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the storefront homepage a bold, high-impact look — cinematic hero with admin-editable headline/CTA, punchier product cards with real ratings, a trust strip, and stronger categories/headers — all theme-aware.

**Architecture:** Presentation-focused changes in a Vue 3 + Vuetify 3 SPA, plus one backend extension (expose 4 new `Setting` keys in the home API and add 4 fields to the existing admin home-settings form — saved by the generic `settings.update` handler, no new controller logic, no migrations). All accents key off the existing `--primary` / `--soft-primary` / `--hov-primary` and the polish-pass tokens `--hp-radius-card` / `--hp-shadow-sm` / `--hp-shadow-md` / `--hp-surface(-muted)`.

**Tech Stack:** Vue 3, Vuetify 3, SCSS, Vite 4, Swiper 11, Laravel 9 (Blade admin + API). App dir: `codecanyon-34858541-the-shop/install` (referred to as `$APP`). Built assets in `$APP/public/build` (git-ignored). App served at `http://localhost:8000` (container `theshop-app` has **no node** — build on host). Admin: `admin@example.com` / `password`.

**Spec:** `docs/superpowers/specs/2026-06-18-homepage-bold-redesign-design.md`

---

## The Build + Verify Loop (use after every visual task)

No JS unit-test harness exists for styling. "Verification" per task means:

```bash
cd codecanyon-34858541-the-shop/install
npm run build            # must end with "✓ built in …" and NO "error during build"
```

Then reload `http://localhost:8000/`, screenshot at 1440 and 375 widths, check the task's **Acceptance criteria**, and confirm **0 console errors**. Node deps are already installed. `public/build` is git-ignored, so a bad build never lands in a commit.

**Theme-aware rule (every task):** never write a brand hex. Use `var(--primary)`, `var(--hov-primary)`, `var(--soft-primary)`, the `--hp-*` tokens, or `color-mix(...)` derivations. Mirror directional values for RTL via `.v-locale--is-rtl`. Guard hover transforms behind `@media (prefers-reduced-motion: reduce)`.

**i18n rule:** this app's `$t('key')` returns the **key string** when the translation is missing (truthy), so `$t('x') || 'Default'` does NOT fall back. For any new user-facing string use the computed-label pattern: `(t && t !== 'key') ? t : 'Default'` (as already done in `HomeAboutText.vue`).

---

## File Structure

- **Modify** `$APP/app/Http/Controllers/Api/SettingController.php` — add a `hero` object to the cached `sliders` payload (Task 1).
- **Modify** `$APP/resources/views/backend/website_settings/pages/home_page_edit.blade.php` — add a "Hero Text" form (4 fields) posting to `settings.update` (Task 1).
- **Modify** `$APP/resources/js/components/home/HomeSliders.vue` — cinematic hero overlay + CTA + promo side cards (Task 2).
- **Modify** `$APP/resources/js/components/product/ProductBox.vue` — bolder style-one card, conditional stars, full-width add-to-cart (Task 3).
- **Create** `$APP/resources/js/components/home/HomeTrustStrip.vue` — trust strip (Task 4).
- **Modify** `$APP/resources/js/pages/Home.vue` — mount `<HomeTrustStrip />` under hero (Task 4).
- **Modify** `$APP/resources/sass/main.scss` — larger `.section-title`, shared bold tweaks (Task 5).
- **Modify** `$APP/resources/js/components/home/HomePopularCategories.vue` — stronger tiles (Task 5).

---

## Task 1: Hero CMS fields (backend — API + admin form)

The generic `SettingController@update` (route `settings.update`) upserts any field listed in `types[]` into a `Setting` row and calls `cache_clear()`. So hero text needs only (a) admin form fields and (b) exposure in the home API. No controller/migration changes.

**Files:**
- Modify: `$APP/app/Http/Controllers/Api/SettingController.php:22-39` (the `sliders` case)
- Modify: `$APP/resources/views/backend/website_settings/pages/home_page_edit.blade.php` (add a form near the top, after the opening sliders form)

- [ ] **Step 1: Extend the `sliders` API payload with a `hero` object**

In `$APP/app/Http/Controllers/Api/SettingController.php`, replace the `sliders` case (lines 22-39):

```php
            case 'sliders':
                $data = Cache::remember('sliders', 86400, function () {
                    return [
                        'one' => get_setting('home_slider_1_images')
                            ? banner_array_generate(get_setting('home_slider_1_images'), get_setting('home_slider_1_links'))
                            : [],
                        'two' => get_setting('home_slider_2_images')
                            ? banner_array_generate(get_setting('home_slider_2_images'), get_setting('home_slider_2_links'))
                            : [],
                        'three' => get_setting('home_slider_3_images')
                            ? banner_array_generate(get_setting('home_slider_3_images'), get_setting('home_slider_3_links'))
                            : [],
                        'four' => get_setting('home_slider_4_images')
                            ? banner_array_generate(get_setting('home_slider_4_images'), get_setting('home_slider_4_links'))
                            : [],
                    ];
                });
                break;
```

with (adds the `hero` key only):

```php
            case 'sliders':
                $data = Cache::remember('sliders', 86400, function () {
                    return [
                        'hero' => [
                            'headline'  => get_setting('home_hero_headline'),
                            'subtext'   => get_setting('home_hero_subtext'),
                            'cta_label' => get_setting('home_hero_cta_label'),
                            'cta_link'  => get_setting('home_hero_cta_link'),
                        ],
                        'one' => get_setting('home_slider_1_images')
                            ? banner_array_generate(get_setting('home_slider_1_images'), get_setting('home_slider_1_links'))
                            : [],
                        'two' => get_setting('home_slider_2_images')
                            ? banner_array_generate(get_setting('home_slider_2_images'), get_setting('home_slider_2_links'))
                            : [],
                        'three' => get_setting('home_slider_3_images')
                            ? banner_array_generate(get_setting('home_slider_3_images'), get_setting('home_slider_3_links'))
                            : [],
                        'four' => get_setting('home_slider_4_images')
                            ? banner_array_generate(get_setting('home_slider_4_images'), get_setting('home_slider_4_links'))
                            : [],
                    ];
                });
                break;
```

- [ ] **Step 2: Add the "Hero Text" admin form to `home_page_edit.blade.php`**

Open `$APP/resources/views/backend/website_settings/pages/home_page_edit.blade.php`. Find the first sliders form — it begins at line ~17 with `<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">`. Directly **above** that line, insert this self-contained card form:

```blade
				<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
					@csrf
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0 h6">{{ translate('Hero Text & Call To Action') }}</h5>
							<button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
						</div>
						<div class="card-body">
							<input type="hidden" name="types[]" value="home_hero_headline">
							<input type="hidden" name="types[]" value="home_hero_subtext">
							<input type="hidden" name="types[]" value="home_hero_cta_label">
							<input type="hidden" name="types[]" value="home_hero_cta_link">
							<div class="form-group">
								<label>{{ translate('Headline') }}</label>
								<input type="text" name="home_hero_headline" value="{{ get_setting('home_hero_headline') }}" class="form-control" placeholder="{{ translate('e.g. Everyday essentials, delivered') }}">
							</div>
							<div class="form-group">
								<label>{{ translate('Subtext') }}</label>
								<input type="text" name="home_hero_subtext" value="{{ get_setting('home_hero_subtext') }}" class="form-control" placeholder="{{ translate('Short supporting line') }}">
							</div>
							<div class="row">
								<div class="col-md-6 form-group">
									<label>{{ translate('Button Label') }}</label>
									<input type="text" name="home_hero_cta_label" value="{{ get_setting('home_hero_cta_label') }}" class="form-control" placeholder="{{ translate('e.g. Shop the sale') }}">
								</div>
								<div class="col-md-6 form-group">
									<label>{{ translate('Button Link') }}</label>
									<input type="text" name="home_hero_cta_link" value="{{ get_setting('home_hero_cta_link') }}" class="form-control" placeholder="/products">
								</div>
							</div>
							<small class="text-muted">{{ translate('Leave Headline empty to show the hero images without any text overlay.') }}</small>
						</div>
					</div>
				</form>

```

(The generic `settings.update` handler reads `types[]` and upserts each named field, then `cache_clear()` flushes the `sliders` cache automatically.)

- [ ] **Step 3: Seed test values + verify the API returns them**

Set values directly (no UI needed) and confirm the API payload, flushing cache first:

```bash
cd codecanyon-34858541-the-shop/install
docker compose exec -T app php artisan tinker --execute="
foreach ([
  'home_hero_headline'=>'Everyday essentials, delivered',
  'home_hero_subtext'=>'Up to 40% off across the store. Fresh deals every day.',
  'home_hero_cta_label'=>'Shop the sale',
  'home_hero_cta_link'=>'/products',
] as \$t=>\$v){ \App\Models\Setting::updateOrCreate(['type'=>\$t],['value'=>\$v]); }
Cache::flush();
echo 'seeded';
"
curl -s http://localhost:8000/api/setting/home/sliders | python3 -c "import sys,json; d=json.load(sys.stdin); print(json.dumps(d['data']['hero'], indent=2))"
```

Expected: the `hero` object prints with the four seeded values (confirms API + cache flush + settings save path all work).

> If the API route differs, find it: `grep -rn "home/{section}\|home_setting\|setting/home" $APP/routes/api.php`. Use the matching path.

- [ ] **Step 4: Commit**

```bash
git -C /home/tanmoy/Projects/Shop/theshop add \
  codecanyon-34858541-the-shop/install/app/Http/Controllers/Api/SettingController.php \
  codecanyon-34858541-the-shop/install/resources/views/backend/website_settings/pages/home_page_edit.blade.php
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): admin-editable hero text + expose in home API

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

**Acceptance criteria:** the `sliders` API includes a `hero` object; admin home-settings page has a "Hero Text & Call To Action" card whose Save persists the four `home_hero_*` settings; clearing Headline leaves an empty (falsy) value.

---

## Task 2: Cinematic hero (`HomeSliders.vue`)

Wrap the existing main banner column in a relative container and overlay the hero text + CTA read from `res.data.data.hero`. Restyle side banners as rounded promo cards. The component already has the `hp-hero` wrapper class and `:deep` framing from the polish pass.

**Files:** Modify `$APP/resources/js/components/home/HomeSliders.vue`.

- [ ] **Step 1: Store the hero object on fetch**

In the `<script>` `data()`, add a `hero` field. Replace:

```js
  data: () => ({
    loading: true,
    sliders: null,
    carouselOption: {
```

with:

```js
  data: () => ({
    loading: true,
    sliders: null,
    hero: null,
    carouselOption: {
```

And in `created()`, set it. Replace:

```js
    if (res.data.success) {
      this.sliders = res.data.data;
      this.loading = false;
    }
```

with:

```js
    if (res.data.success) {
      this.sliders = res.data.data;
      this.hero = res.data.data.hero || null;
      this.loading = false;
    }
```

- [ ] **Step 2: Add a computed CTA-visibility helper**

Add a `computed` block to the component options (there is none yet). Insert right after the `data: () => ({ … }),` object and before `async created() {`:

```js
  computed: {
    heroHasText() {
      return !!(this.hero && this.hero.headline && this.hero.headline.trim());
    },
    heroHasCta() {
      return !!(this.hero && this.hero.cta_label && this.hero.cta_label.trim() && this.hero.cta_link && this.hero.cta_link.trim());
    },
  },
```

- [ ] **Step 3: Wrap the main banner column with a relative container + overlay**

In the `v-else` row, the first main column is:

```html
        <v-col
          cols="12"
          lg="6"
          class=""
        >
```

…containing the (uncommented) `<swiper … class="mySwiper">` for `sliders.one`. Wrap that swiper and add the overlay. Replace the main column's swiper block:

```html
          <swiper
              :spaceBetween="30"
              :centeredSlides="true"
              :autoplay=carouselOption.autoplay
              :modules="modules"
              class="mySwiper"
  >
                  <swiper-slide
                    v-for="(slider, i) in sliders.one"
                    :key="i"
                    class=""
                  >
                    <banner
                      :loading="false"
                      :banner="slider"
                    />
                  </swiper-slide>
          </swiper>
```

with (adds a relative wrapper + absolutely-positioned overlay):

```html
          <div class="hp-hero-main">
            <swiper
                :spaceBetween="30"
                :centeredSlides="true"
                :autoplay=carouselOption.autoplay
                :modules="modules"
                class="mySwiper"
            >
                    <swiper-slide
                      v-for="(slider, i) in sliders.one"
                      :key="i"
                      class=""
                    >
                      <banner
                        :loading="false"
                        :banner="slider"
                      />
                    </swiper-slide>
            </swiper>
            <div class="hp-hero-overlay" v-if="heroHasText">
              <h1 class="hp-hero-title">{{ hero.headline }}</h1>
              <p class="hp-hero-sub" v-if="hero.subtext">{{ hero.subtext }}</p>
              <a class="hp-hero-cta" v-if="heroHasCta" :href="hero.cta_link">{{ hero.cta_label }}</a>
            </div>
          </div>
```

- [ ] **Step 4: Add the hero overlay/promo styles to the scoped block**

In the `<style scoped>` block, after the existing `.hp-hero :deep(...)` rules (just before the final `@media (prefers-reduced-motion: reduce)` block), add:

```css
.hp-hero-main { position: relative; }
.hp-hero-overlay {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; justify-content: center;
  padding: 0 8%;
  border-radius: var(--hp-radius-card);
  background: linear-gradient(90deg, rgba(8,11,15,.82) 0%, rgba(8,11,15,.45) 45%, rgba(8,11,15,0) 78%);
  pointer-events: none;
}
.hp-hero-overlay > * { pointer-events: auto; }
.hp-hero-title {
  color: #fff; font-weight: 800; letter-spacing: -.02em;
  font-size: clamp(22px, 3.2vw, 44px); line-height: 1.1; margin: 0 0 12px; max-width: 60%;
}
.hp-hero-sub { color: #E6E8EC; font-size: clamp(13px, 1.3vw, 17px); margin: 0 0 22px; max-width: 50%; }
.hp-hero-cta {
  display: inline-flex; align-items: center; width: max-content;
  background: var(--primary); color: #fff; font-weight: 700; font-size: 15px;
  padding: 12px 26px; border-radius: 999px; text-decoration: none;
  box-shadow: 0 8px 20px color-mix(in srgb, var(--primary) 40%, transparent);
  transition: transform .15s ease-out, background .15s ease-out;
}
.hp-hero-cta:hover { background: var(--hov-primary); transform: translateY(-2px); }
.v-locale--is-rtl .hp-hero-overlay {
  background: linear-gradient(270deg, rgba(8,11,15,.82) 0%, rgba(8,11,15,.45) 45%, rgba(8,11,15,0) 78%);
  text-align: right;
}
@media (max-width: 600px) {
  .hp-hero-title, .hp-hero-sub { max-width: 80%; }
  .hp-hero-overlay { padding: 0 6%; }
}
@media (prefers-reduced-motion: reduce) {
  .hp-hero-cta { transition: none; } .hp-hero-cta:hover { transform: none; }
}
```

- [ ] **Step 5: Build + verify**

Run the Build + Verify Loop. **Acceptance criteria:** the main hero banner shows the seeded headline + subtext + a pill CTA over a dark left gradient; CTA navigates to `/products`; text is legible over the banner image; side banners still render; no overflow at 375/1440; clearing `home_hero_headline` (re-seed empty, flush cache, reload) shows the hero with no overlay.

- [ ] **Step 6: Commit**

```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/js/components/home/HomeSliders.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): cinematic hero with overlay headline + themed CTA

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 3: Bolder product card (`ProductBox.vue`, style "one")

Builds on the polish-pass style-one card (rounded depth, hover lift+zoom, pill badge, floating wishlist FAB). Add: real conditional star ratings, a more prominent price, and a full-width "Add to cart" button that fills with `--primary` on hover. All scoped to `.product-box-one`.

**Files:** Modify `$APP/resources/js/components/product/ProductBox.vue`.

- [ ] **Step 1: Add a conditional star-rating row (style one only)**

In the template, the price block for style one is the `<div :class="[ boxStyle == 'two' ? 'order-2 fs-14 lh-1' : 'fs-16 mb-2']">` … `</div>` followed by the `<h5 …>` product name. Directly **after** that `<h5>…</h5>` name block (the element starting `<h5 :class="['opacity-60 fw-400 mb-2 lh-1-6', …`), insert:

```html
            <div
              v-if="boxStyle == 'one' && productDetails.reviews_count > 0"
              class="hp-stars d-flex align-center mb-2"
            >
              <i
                v-for="n in 5"
                :key="n"
                :class="n <= Math.round(productDetails.rating) ? 'las la-star' : 'la la-star-o'"
              ></i>
              <span class="hp-stars__count">({{ productDetails.reviews_count }})</span>
            </div>
```

- [ ] **Step 2: Add a full-width "Add to cart" for style one; hide the inline action row for style one**

The shared action row is:

```html
            <div
              class="d-flex align-center"
              v-if="boxStyle != 'two'"
            >
```

Change its guard so it does **not** render for style one:

```html
            <div
              class="d-flex align-center"
              v-if="boxStyle != 'two' && boxStyle != 'one'"
            >
```

Then, immediately **after** that entire action-row `</div>` (the one closing the `d-flex align-center` block, right before the closing of `<div :class="['px-3 d-flex flex-column', …`), add the style-one full-width button:

```html
            <div v-if="boxStyle == 'one'" class="hp-buy-row">
              <button
                v-if="productDetails.stock"
                type="button"
                class="hp-buy-btn"
                @click="showAddToCartDialog({status:true,slug:productDetails.slug})"
              >
                <i class="las la-shopping-cart fs-18 me-1"></i>
                <span>{{ $t('add_to_cart') && $t('add_to_cart') !== 'add_to_cart' ? $t('add_to_cart') : 'Add to cart' }}</span>
              </button>
              <span v-else class="hp-buy-btn hp-buy-btn--disabled">{{ $t('out_of_stock') }}</span>
            </div>
```

- [ ] **Step 3: Add the scoped styles**

In the existing `<style scoped>` block (it currently ends with the reduced-motion `@media` for the card), add these rules **before** the closing `</style>`:

```css
.product-box-one .hp-stars { gap: 1px; color: var(--primary); font-size: 13px; }
.product-box-one .hp-stars .la-star-o { color: color-mix(in srgb, var(--primary) 35%, #cfd4dc); }
.product-box-one .hp-stars__count { color: #9097a1; font-size: 12px; margin-left: 6px; }
.product-box-one .hp-buy-row { margin-top: 6px; }
.product-box-one .hp-buy-btn {
  width: 100%; display: inline-flex; align-items: center; justify-content: center;
  border: 0; cursor: pointer;
  background: var(--hp-surface-muted); color: var(--primary);
  font-weight: 700; font-size: 14px; padding: 10px 12px; border-radius: 10px;
  transition: background .15s ease-out, color .15s ease-out;
}
.product-box-one .rounded.border:hover .hp-buy-btn { background: var(--primary); color: #fff; }
.product-box-one .hp-buy-btn--disabled { background: var(--hp-surface-muted); color: #9097a1; cursor: default; }
.product-box-one .rounded.border:hover .hp-buy-btn--disabled { background: var(--hp-surface-muted); color: #9097a1; }
@media (prefers-reduced-motion: reduce) {
  .product-box-one .hp-buy-btn { transition: none; }
}
```

- [ ] **Step 4: Build + verify**

Run the Build + Verify Loop. **Acceptance criteria:** style-one cards show a full-width "Add to cart" that fills with the theme primary on card hover and opens the add-to-cart dialog; out-of-stock shows a muted label; stars appear only on products with `reviews_count > 0` (seeded demo products show none); price still prominent.

- [ ] **Step 5: Regression check on shared usage**

Visit a category and a search page; confirm `boxStyle` two/three/four cards are unchanged (no full-width button, no stars row injected, inline action row still present):

```
http://localhost:8000/category/<slug-from-Popular-Categories>
http://localhost:8000/search?q=a
```

- [ ] **Step 6: Commit**

```bash
git -C /home/tanmoy/Projects/Shop/theshop add codecanyon-34858541-the-shop/install/resources/js/components/product/ProductBox.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): bolder style-one card — real stars, full-width add-to-cart

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 4: Trust strip (`HomeTrustStrip.vue` + `Home.vue`)

A new 4-item presentational row under the hero. Static translatable text (computed labels), Line Awesome icons, themed accents. Reflows to 2×2 on mobile.

**Files:**
- Create: `$APP/resources/js/components/home/HomeTrustStrip.vue`
- Modify: `$APP/resources/js/pages/Home.vue`

- [ ] **Step 1: Create `HomeTrustStrip.vue`**

Create `$APP/resources/js/components/home/HomeTrustStrip.vue` with:

```html
<template>
  <v-container class="px-3 px-md-3">
    <div class="hp-trust">
      <div class="hp-trust__item" v-for="(item, i) in items" :key="i">
        <div class="hp-trust__ico"><i :class="item.icon"></i></div>
        <div>
          <b class="hp-trust__title">{{ item.title }}</b>
          <span class="hp-trust__sub">{{ item.sub }}</span>
        </div>
      </div>
    </div>
  </v-container>
</template>

<script>
export default {
  computed: {
    items() {
      return [
        { icon: "las la-shipping-fast", title: this.t("trust_free_delivery", "Free Delivery"), sub: this.t("trust_free_delivery_sub", "On qualifying orders") },
        { icon: "las la-undo-alt", title: this.t("trust_easy_returns", "Easy Returns"), sub: this.t("trust_easy_returns_sub", "Hassle-free refunds") },
        { icon: "las la-lock", title: this.t("trust_secure_payment", "Secure Payments"), sub: this.t("trust_secure_payment_sub", "100% protected") },
        { icon: "las la-headset", title: this.t("trust_support", "Support 24/7"), sub: this.t("trust_support_sub", "We're here to help") },
      ];
    },
  },
  methods: {
    t(key, fallback) {
      const v = this.$t(key);
      return v && v !== key ? v : fallback;
    },
  },
};
</script>

<style scoped>
.hp-trust {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
  margin: 18px 0 6px;
}
.hp-trust__item {
  display: flex; align-items: center; gap: 12px;
  background: var(--hp-surface); border-radius: 12px; padding: 14px 16px;
  box-shadow: var(--hp-shadow-sm);
}
.hp-trust__ico {
  width: 42px; height: 42px; flex: 0 0 auto; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: var(--soft-primary); color: var(--primary); font-size: 20px;
}
.hp-trust__title { display: block; font-size: 14px; line-height: 1.2; }
.hp-trust__sub { font-size: 12px; color: #6B7280; }
@media (max-width: 600px) {
  .hp-trust { grid-template-columns: repeat(2, 1fr); }
}
</style>
```

> `--soft-primary` is injected globally in `app.blade.php` (same place as `--primary`). If a build/runtime check shows it undefined, substitute `color-mix(in srgb, var(--primary) 12%, transparent)` for the `.hp-trust__ico` background.

- [ ] **Step 2: Mount it under the hero in `Home.vue`**

In `$APP/resources/js/pages/Home.vue`, replace:

```html
    <!-- slider area -->
    <HomeSliders />

    <!-- popular categories -->
    <HomePopularCategories />
```

with:

```html
    <!-- slider area -->
    <HomeSliders />

    <!-- trust strip -->
    <HomeTrustStrip />

    <!-- popular categories -->
    <HomePopularCategories />
```

Add the import alongside the other imports (after the `HomeSliders` import line):

```js
import HomeTrustStrip from "../components/home/HomeTrustStrip.vue";
```

And register it in `components: { … }` (add the entry next to `HomeSliders,`):

```js
    HomeSliders,
    HomeTrustStrip,
```

- [ ] **Step 3: Build + verify**

Run the Build + Verify Loop. **Acceptance criteria:** a 4-up trust row appears directly under the hero on desktop and 2×2 on mobile; icons are tinted with the theme primary on a soft-primary chip; text readable; 0 console errors.

- [ ] **Step 4: Commit**

```bash
git -C /home/tanmoy/Projects/Shop/theshop add \
  codecanyon-34858541-the-shop/install/resources/js/components/home/HomeTrustStrip.vue \
  codecanyon-34858541-the-shop/install/resources/js/pages/Home.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): trust strip under hero (themed, translatable)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 5: Stronger categories + larger section headers

**Files:**
- Modify: `$APP/resources/sass/main.scss` (the `/* === Homepage polish === */` block — the `.section-title` rules)
- Modify: `$APP/resources/js/components/home/HomePopularCategories.vue` (the scoped `.hp-cat-*` rules)

- [ ] **Step 1: Scale up the section header in `main.scss`**

In `$APP/resources/sass/main.scss`, find the `.section-title__text` rules in the polish block and replace:

```scss
.section-title__text {
  display: flex; align-items: center; gap: 12px;
  font-size: 20px; font-weight: 700; line-height: 1.2; margin: 0;
}
.section-title__text::before {
  content: ""; display: inline-block; width: 4px; height: 22px;
  border-radius: 4px; background: var(--primary); flex: 0 0 auto;
}
@media (min-width: 960px) { .section-title__text { font-size: 26px; } }
```

with (bolder + larger accent bar):

```scss
.section-title__text {
  display: flex; align-items: center; gap: 12px;
  font-size: 22px; font-weight: 800; letter-spacing: -.01em; line-height: 1.15; margin: 0;
}
.section-title__text::before {
  content: ""; display: inline-block; width: 6px; height: 26px;
  border-radius: 4px; background: var(--primary); flex: 0 0 auto;
}
@media (min-width: 960px) { .section-title__text { font-size: 30px; } }
```

- [ ] **Step 2: Strengthen the category tile in `HomePopularCategories.vue`**

In the scoped `<style>` block, replace:

```css
.hp-cat-tile {
  border-radius: var(--hp-radius-card); background: var(--hp-surface-muted);
  padding: 12px; transition: transform .2s ease-out, box-shadow .2s ease-out;
}
.hp-cat-tile:hover { transform: translateY(-3px); box-shadow: var(--hp-shadow-md); }
```

with:

```css
.hp-cat-tile {
  border-radius: var(--hp-radius-card); background: var(--hp-surface);
  border: 1px solid color-mix(in srgb, var(--primary) 10%, #eceef1);
  padding: 14px; box-shadow: var(--hp-shadow-sm);
  transition: transform .2s ease-out, box-shadow .2s ease-out, border-color .2s ease-out;
}
.hp-cat-tile:hover { transform: translateY(-4px); box-shadow: var(--hp-shadow-md); border-color: var(--primary); }
```

And replace the category name rule:

```css
.hp-cat-name { margin-top: 10px; font-size: 13px; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
```

with:

```css
.hp-cat-name { margin-top: 10px; font-size: 13px; font-weight: 700;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
```

- [ ] **Step 3: Build + verify**

Run the Build + Verify Loop. **Acceptance criteria:** section headers are visibly larger/bolder with a taller primary accent bar; category tiles read as crisp bordered cards that gain a primary border + lift on hover; labels bolder; no layout breakage at 375/1440.

- [ ] **Step 4: Commit**

```bash
git -C /home/tanmoy/Projects/Shop/theshop add \
  codecanyon-34858541-the-shop/install/resources/sass/main.scss \
  codecanyon-34858541-the-shop/install/resources/js/components/home/HomePopularCategories.vue
git -C /home/tanmoy/Projects/Shop/theshop commit -m "feat(shop/home): larger section headers + crisper category tiles

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 6: Cross-cutting verification

- [ ] **Step 1: Responsive** — screenshot the homepage at widths 375, 768, 1024, 1440. No horizontal scroll; hero overlay text stays legible and within bounds; trust strip reflows 4→2 columns; cards reflow.

- [ ] **Step 2: Theme swap** — at `http://localhost:8000/admin/theme` apply a different industry theme (e.g. Pharmacy `#0D9488`), reload home. Confirm the hero CTA, star color, "Add to cart" hover fill, trust icons, category hover border, and section accent bars all follow the new primary. Then re-apply the original theme (Supershop `#16A34A`) to restore.

- [ ] **Step 3: Reduced motion** — emulate `prefers-reduced-motion: reduce`; confirm hover lifts/zooms/CTA transforms are disabled.

- [ ] **Step 4: Hero empty-state** — seed `home_hero_headline` to empty (`Setting::updateOrCreate(['type'=>'home_hero_headline'],['value'=>'']); Cache::flush();`), reload, confirm the hero renders cleanly with no overlay/empty boxes; then restore the headline.

- [ ] **Step 5: Focus states** — keyboard-tab through hero CTA, category tiles, product "Add to cart"; confirm visible focus rings.

- [ ] **Step 6: Final before/after** — full-page screenshots at 1440 and 375; compare against the earlier `home-desktop-final.jpeg` / baseline `current-home.jpeg`.

- [ ] **Step 7: Final build is clean** — `npm run build` ends with `✓ built`, no errors; 0 console errors on home.

**Acceptance criteria:** all pass; no regressions on category/search pages from Task 3.

---

## Self-Review Notes (author)

- **Spec coverage:** Hero overlay+CTA+promos → T2; hero CMS fields → T1; punchy cards + real stars + add-to-cart → T3; trust strip → T4; bolder categories+headers → T5; theme-aware → every task + T6 Step 2; i18n key-fallback → T3/T4 computed labels; RTL → T2 overrides; reduced-motion → T2/T3/T6; build/verify → loop + T6. All spec sections mapped.
- **No migrations / no new tables:** hero fields are `Setting` rows created on save by the existing generic handler.
- **Shared `ProductBox` risk:** all new markup/CSS gated on `boxStyle == 'one'` / `.product-box-one`; explicit regression check T3 Step 5.
- **Out of scope preserved:** no new product/shop sections (trust strip is the one approved new row), no reordering, no CMS changes beyond the 4 hero fields.
- **Known content gaps** (empty Medicines/Wellness/Personal Care sections, broken demo image slots) remain out of scope — admin content, not styling.
