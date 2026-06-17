# Store Industry Themes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a shop admin switch the storefront between ready-made industry themes (Electronics, Supershop, Pharmacy, Pet Shop) — applying a curated look (primary color, home layout, generated banners) and, optionally, a tagged-and-reversible demo catalog.

**Architecture:** A theme is a code-defined preset whose values are written into The Shop's existing `settings` table (the Vue SPA already renders from it, so no frontend rebuild). A `ThemeApplier` service applies/resets themes inside a DB transaction and records every artifact it creates or overwrites in two tracking tables, so switching/resetting removes only theme-seeded items and never the merchant's own data. No core CodeCanyon tables are modified.

**Tech Stack:** PHP 8.1, Laravel 9 (The Shop), MySQL/MariaDB (prod) + SQLite in-memory (tests, via the existing `sqlite_testing` connection), PHPUnit, SVG banner generation via `Storage`.

**Spec:** `docs/superpowers/specs/2026-06-17-store-industry-themes-design.md`

**Conventions:** TDD throughout (failing test first). Commit after every green step. The Shop app root is `codecanyon-34858541-the-shop/install/` (referred to below as `<shop>/`). Run tests in the existing container:

```bash
# from <shop>/
alias shop-test='docker compose exec -T app php artisan test'
alias shop-art='docker compose exec -T app php artisan'
```

Key facts confirmed against the live DB (do not re-discover):
- Settings: `App\Models\Setting` with columns `type`, `value`. Read via `get_setting($key)`; cached under key `'settings'` → clear with `Cache::forget('settings')`.
- Image settings (`home_slider_*_img`, `home_banner_*_img`) store an **`uploads.id`** integer.
- `uploads` columns used: `file_original_name`, `file_name`, `user_id`, `file_size`, `extension`, `type`.
- `categories` columns: `id, parent_id, level, name, order_level, banner, icon, featured, slug, ...` (global; no `shop_id`). Names also stored in `category_translations(category_id, name, lang)`.
- `products` columns: `id, shop_id, name, slug, published, approved, main_category, lowest_price, highest_price, unit, stock, thumbnail_img, ...` (no `price`/`category_id`). Names also stored in `product_translations(product_id, name, unit, description, lang)`.
- `home_product_section_N_products` is a JSON array of id strings, e.g. `["1","2","3"]`.
- Demo products need `shop_id` = the admin shop id so they pass `published_shops_ids()` and show on the storefront. In tests we set `shop_id = 1`.

---

## Phase A — Foundation: tracking tables + test harness

### Task 1: Migrations for the two tracking tables

**Files:**
- Create: `<shop>/database/migrations/2026_06_17_000001_create_theme_applications_table.php`
- Create: `<shop>/database/migrations/2026_06_17_000002_create_theme_application_items_table.php`

- [ ] **Step 1: Write the `theme_applications` migration**

```php
<?php
// <shop>/database/migrations/2026_06_17_000001_create_theme_applications_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theme_applications', function (Blueprint $table) {
            $table->id();
            $table->string('vertical');           // electronics | supershop | pharmacy | pet_shop
            $table->boolean('demo_loaded')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('theme_applications'); }
};
```

- [ ] **Step 2: Write the `theme_application_items` migration**

```php
<?php
// <shop>/database/migrations/2026_06_17_000002_create_theme_application_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theme_application_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_application_id')->constrained()->cascadeOnDelete();
            $table->string('kind');               // setting | upload | category | product
            $table->unsignedBigInteger('ref_id')->nullable();   // upload/category/product id
            $table->string('setting_type')->nullable();         // settings.type when kind=setting
            $table->text('prior_value')->nullable();            // previous setting value (null = key absent)
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('theme_application_items'); }
};
```

- [ ] **Step 3: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/database/migrations/2026_06_17_0000*
git commit -m "feat(shop/theme): tracking tables for theme applications"
```

---

### Task 2: Eloquent models + test harness

**Files:**
- Create: `<shop>/app/Themes/ThemeApplication.php`
- Create: `<shop>/app/Themes/ThemeApplicationItem.php`
- Create: `<shop>/tests/Theme/ThemeTestCase.php`

- [ ] **Step 1: Write the models**

```php
<?php
// <shop>/app/Themes/ThemeApplication.php
namespace App\Themes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThemeApplication extends Model
{
    protected $fillable = ['vertical', 'demo_loaded', 'applied_at'];
    protected $casts = ['demo_loaded' => 'boolean', 'applied_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(ThemeApplicationItem::class);
    }
}
```

```php
<?php
// <shop>/app/Themes/ThemeApplicationItem.php
namespace App\Themes;

use Illuminate\Database\Eloquent\Model;

class ThemeApplicationItem extends Model
{
    protected $fillable = ['theme_application_id', 'kind', 'ref_id', 'setting_type', 'prior_value'];
}
```

- [ ] **Step 2: Write the test harness**

This builds the minimal schema the theme code touches, isolated from the full store DB. It mirrors the existing `tests/Agent/AgentTestCase.php` pattern.

```php
<?php
// <shop>/tests/Theme/ThemeTestCase.php
namespace Tests\Theme;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class ThemeTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('settings', function ($t) {
            $t->id();
            $t->string('type');
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('uploads', function ($t) {
            $t->id();
            $t->string('file_original_name')->nullable();
            $t->string('file_name')->nullable();
            $t->integer('user_id')->nullable();
            $t->integer('file_size')->nullable();
            $t->string('extension', 10)->nullable();
            $t->string('type', 15)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('categories', function ($t) {
            $t->id();
            $t->integer('parent_id')->default(0);
            $t->integer('level')->default(0);
            $t->string('name');
            $t->integer('order_level')->default(0);
            $t->integer('banner')->nullable();
            $t->integer('icon')->nullable();
            $t->integer('featured')->default(0);
            $t->string('slug')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('category_translations', function ($t) {
            $t->id();
            $t->integer('category_id');
            $t->string('name');
            $t->string('lang')->nullable();
            $t->timestamps();
        });

        Schema::create('products', function ($t) {
            $t->id();
            $t->integer('shop_id')->nullable();
            $t->string('name');
            $t->string('slug');
            $t->integer('published')->default(1);
            $t->integer('approved')->default(1);
            $t->integer('main_category')->nullable();
            $t->decimal('lowest_price', 20, 2)->default(0);
            $t->decimal('highest_price', 20, 2)->default(0);
            $t->string('unit')->nullable();
            $t->integer('stock')->default(0);
            $t->integer('thumbnail_img')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('product_translations', function ($t) {
            $t->id();
            $t->integer('product_id');
            $t->string('name');
            $t->string('unit')->nullable();
            $t->text('description')->nullable();
            $t->string('lang')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('theme_applications', function ($t) {
            $t->id();
            $t->string('vertical');
            $t->boolean('demo_loaded')->default(false);
            $t->timestamp('applied_at')->nullable();
            $t->timestamps();
        });

        Schema::create('theme_application_items', function ($t) {
            $t->id();
            $t->unsignedBigInteger('theme_application_id');
            $t->string('kind');
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('setting_type')->nullable();
            $t->text('prior_value')->nullable();
            $t->timestamps();
        });
    }
}
```

- [ ] **Step 3: Sanity-check the harness boots**

Run: `shop-test --filter=ThemeTestCase`
Expected: PASS (0 tests run, no schema errors). If it errors, the schema is wrong — fix before continuing.

- [ ] **Step 4: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Themes codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "feat(shop/theme): tracking models + sqlite test harness"
```

---

## Phase B — Preset definitions

### Task 3: `ThemePreset` abstract, registry, and four presets

**Files:**
- Create: `<shop>/app/Themes/ThemePreset.php`
- Create: `<shop>/app/Themes/Presets/ElectronicsPreset.php`
- Create: `<shop>/app/Themes/Presets/SupershopPreset.php`
- Create: `<shop>/app/Themes/Presets/PharmacyPreset.php`
- Create: `<shop>/app/Themes/Presets/PetShopPreset.php`
- Test: `<shop>/tests/Theme/ThemePresetTest.php`

Preset interface (used by every later task):
- `key(): string` — slug, e.g. `electronics`
- `label(): string` — display name
- `baseColor(): string` — hex
- `sectionTitles(): array` — exactly 3 home section titles
- `banners(): array` — list of `['slot' => 'home_slider_1_img'|'home_banner_1_img'|..., 'title' => string, 'tagline' => string]`
- `catalog(): array` — `['categories' => [['name'=>..,'children'=>[..]], ..], 'products' => [['name'=>..,'category'=>..,'price'=>float], ..]]`
- static `for(string $key): ThemePreset`
- static `all(): array`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Theme/ThemePresetTest.php
namespace Tests\Theme;

use App\Themes\ThemePreset;

class ThemePresetTest extends ThemeTestCase
{
    public function test_all_returns_four_presets_with_unique_keys(): void
    {
        $presets = ThemePreset::all();
        $this->assertCount(4, $presets);

        $keys = array_map(fn ($p) => $p->key(), $presets);
        $this->assertSame($keys, array_unique($keys));
        $this->assertEqualsCanonicalizing(
            ['electronics', 'supershop', 'pharmacy', 'pet_shop'], $keys
        );
    }

    public function test_for_resolves_by_key_and_exposes_shape(): void
    {
        $p = ThemePreset::for('pharmacy');
        $this->assertSame('pharmacy', $p->key());
        $this->assertSame('#0D9488', $p->baseColor());
        $this->assertCount(3, $p->sectionTitles());
        $this->assertNotEmpty($p->banners());
        $this->assertArrayHasKey('categories', $p->catalog());
        $this->assertArrayHasKey('products', $p->catalog());
    }

    public function test_for_unknown_key_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ThemePreset::for('nope');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=ThemePresetTest`
Expected: FAIL ("Class App\Themes\ThemePreset not found").

- [ ] **Step 3: Write the abstract + registry**

```php
<?php
// <shop>/app/Themes/ThemePreset.php
namespace App\Themes;

use App\Themes\Presets\ElectronicsPreset;
use App\Themes\Presets\PetShopPreset;
use App\Themes\Presets\PharmacyPreset;
use App\Themes\Presets\SupershopPreset;

abstract class ThemePreset
{
    abstract public function key(): string;
    abstract public function label(): string;
    abstract public function baseColor(): string;

    /** @return string[] exactly 3 section titles */
    abstract public function sectionTitles(): array;

    /** @return array<int,array{slot:string,title:string,tagline:string}> */
    abstract public function banners(): array;

    /** @return array{categories: array, products: array} */
    abstract public function catalog(): array;

    /** @return ThemePreset[] */
    public static function all(): array
    {
        return [
            new ElectronicsPreset(),
            new SupershopPreset(),
            new PharmacyPreset(),
            new PetShopPreset(),
        ];
    }

    public static function for(string $key): ThemePreset
    {
        foreach (self::all() as $preset) {
            if ($preset->key() === $key) {
                return $preset;
            }
        }
        throw new \InvalidArgumentException("Unknown theme preset: {$key}");
    }
}
```

- [ ] **Step 4: Write the four presets**

```php
<?php
// <shop>/app/Themes/Presets/ElectronicsPreset.php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class ElectronicsPreset extends ThemePreset
{
    public function key(): string { return 'electronics'; }
    public function label(): string { return 'Electronics'; }
    public function baseColor(): string { return '#2563EB'; }
    public function sectionTitles(): array { return ['Featured', 'Best Sellers', 'New Arrivals']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Electronics', 'tagline' => 'Tech that moves you'],
            ['slot' => 'home_banner_1_img', 'title' => 'Top Gadgets', 'tagline' => 'Shop the latest'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Phones', 'children' => ['Smartphones', 'Accessories']],
                ['name' => 'Computers', 'children' => ['Laptops', 'Monitors']],
                ['name' => 'Audio', 'children' => ['Headphones']],
            ],
            'products' => [
                ['name' => 'Demo Smartphone X', 'category' => 'Smartphones', 'price' => 299.00],
                ['name' => 'Demo Phone Case', 'category' => 'Accessories', 'price' => 12.00],
                ['name' => 'Demo Laptop Pro', 'category' => 'Laptops', 'price' => 899.00],
                ['name' => 'Demo 27" Monitor', 'category' => 'Monitors', 'price' => 199.00],
                ['name' => 'Demo Wireless Headphones', 'category' => 'Headphones', 'price' => 79.00],
            ],
        ];
    }
}
```

```php
<?php
// <shop>/app/Themes/Presets/SupershopPreset.php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class SupershopPreset extends ThemePreset
{
    public function key(): string { return 'supershop'; }
    public function label(): string { return 'Supershop / Grocery'; }
    public function baseColor(): string { return '#16A34A'; }
    public function sectionTitles(): array { return ['Daily Deals', 'Groceries', 'Household']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Supershop', 'tagline' => 'Fresh, every day'],
            ['slot' => 'home_banner_1_img', 'title' => 'Daily Deals', 'tagline' => 'Save on essentials'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Fruits & Vegetables', 'children' => []],
                ['name' => 'Beverages', 'children' => []],
                ['name' => 'Snacks', 'children' => []],
                ['name' => 'Household', 'children' => ['Cleaning', 'Paper']],
            ],
            'products' => [
                ['name' => 'Demo Fresh Apples 1kg', 'category' => 'Fruits & Vegetables', 'price' => 3.50],
                ['name' => 'Demo Orange Juice 1L', 'category' => 'Beverages', 'price' => 2.20],
                ['name' => 'Demo Potato Chips', 'category' => 'Snacks', 'price' => 1.80],
                ['name' => 'Demo Dish Soap', 'category' => 'Cleaning', 'price' => 2.90],
                ['name' => 'Demo Paper Towels', 'category' => 'Paper', 'price' => 4.00],
            ],
        ];
    }
}
```

```php
<?php
// <shop>/app/Themes/Presets/PharmacyPreset.php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class PharmacyPreset extends ThemePreset
{
    public function key(): string { return 'pharmacy'; }
    public function label(): string { return 'Pharmacy'; }
    public function baseColor(): string { return '#0D9488'; }
    public function sectionTitles(): array { return ['Medicines', 'Wellness', 'Personal Care']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Pharmacy', 'tagline' => 'Your health, delivered'],
            ['slot' => 'home_banner_1_img', 'title' => 'Wellness', 'tagline' => 'Feel your best'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Medicines', 'children' => ['OTC', 'Prescription']],
                ['name' => 'Vitamins & Supplements', 'children' => []],
                ['name' => 'Personal Care', 'children' => []],
                ['name' => 'Baby Care', 'children' => []],
            ],
            'products' => [
                ['name' => 'Demo Pain Relief Tablets', 'category' => 'OTC', 'price' => 5.00],
                ['name' => 'Demo Antibiotic (Rx)', 'category' => 'Prescription', 'price' => 14.00],
                ['name' => 'Demo Vitamin C 1000mg', 'category' => 'Vitamins & Supplements', 'price' => 9.50],
                ['name' => 'Demo Hand Sanitizer', 'category' => 'Personal Care', 'price' => 3.20],
                ['name' => 'Demo Baby Lotion', 'category' => 'Baby Care', 'price' => 6.80],
            ],
        ];
    }
}
```

```php
<?php
// <shop>/app/Themes/Presets/PetShopPreset.php
namespace App\Themes\Presets;

use App\Themes\ThemePreset;

class PetShopPreset extends ThemePreset
{
    public function key(): string { return 'pet_shop'; }
    public function label(): string { return 'Pet Shop'; }
    public function baseColor(): string { return '#D97706'; }
    public function sectionTitles(): array { return ['Dogs', 'Cats', 'Pet Food & Accessories']; }

    public function banners(): array
    {
        return [
            ['slot' => 'home_slider_1_img', 'title' => 'Pet Shop', 'tagline' => 'Everything your pet loves'],
            ['slot' => 'home_banner_1_img', 'title' => 'Pet Food', 'tagline' => 'Healthy & happy'],
        ];
    }

    public function catalog(): array
    {
        return [
            'categories' => [
                ['name' => 'Dogs', 'children' => ['Dog Food', 'Dog Toys']],
                ['name' => 'Cats', 'children' => ['Cat Food', 'Cat Litter']],
                ['name' => 'Birds', 'children' => []],
                ['name' => 'Aquarium', 'children' => []],
            ],
            'products' => [
                ['name' => 'Demo Dry Dog Food 2kg', 'category' => 'Dog Food', 'price' => 18.00],
                ['name' => 'Demo Squeaky Bone Toy', 'category' => 'Dog Toys', 'price' => 6.00],
                ['name' => 'Demo Cat Food Salmon', 'category' => 'Cat Food', 'price' => 15.00],
                ['name' => 'Demo Clumping Litter 5L', 'category' => 'Cat Litter', 'price' => 9.00],
                ['name' => 'Demo Bird Seed Mix', 'category' => 'Birds', 'price' => 7.50],
            ],
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `shop-test --filter=ThemePresetTest`
Expected: PASS (all 3 cases).

- [ ] **Step 6: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Themes codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "feat(shop/theme): preset definitions for 4 verticals"
```

---

## Phase C — Banner generation

### Task 4: `BannerGenerator`

Generates one SVG image per banner spec, writes it to the `public` disk under `uploads/themes/`, inserts an `uploads` row, and returns a map of `slot => upload_id`.

**Files:**
- Create: `<shop>/app/Themes/BannerGenerator.php`
- Test: `<shop>/tests/Theme/BannerGeneratorTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Theme/BannerGeneratorTest.php
namespace Tests\Theme;

use App\Themes\BannerGenerator;
use App\Themes\ThemePreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerGeneratorTest extends ThemeTestCase
{
    public function test_generates_an_upload_and_file_per_banner_slot(): void
    {
        Storage::fake('public');
        $preset = ThemePreset::for('pharmacy');

        $map = (new BannerGenerator())->generate($preset, adminUserId: 1);

        // one entry per banner spec
        $this->assertSame(array_column($preset->banners(), 'slot'), array_keys($map));

        foreach ($map as $slot => $uploadId) {
            $upload = DB::table('uploads')->find($uploadId);
            $this->assertNotNull($upload, "upload row missing for {$slot}");
            $this->assertSame('svg', $upload->extension);
            Storage::disk('public')->assertExists($upload->file_name);
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=BannerGeneratorTest`
Expected: FAIL ("Class App\Themes\BannerGenerator not found").

- [ ] **Step 3: Write the implementation**

```php
<?php
// <shop>/app/Themes/BannerGenerator.php
namespace App\Themes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerGenerator
{
    /**
     * Generate one SVG banner per preset slot.
     *
     * @return array<string,int> slot => uploads.id
     */
    public function generate(ThemePreset $preset, int $adminUserId): array
    {
        $disk = Storage::disk('public');
        $map = [];

        foreach ($preset->banners() as $spec) {
            $svg = $this->svg($preset->baseColor(), $spec['title'], $spec['tagline']);
            $fileName = 'uploads/themes/' . $preset->key() . '-' . $spec['slot'] . '-' . Str::random(8) . '.svg';
            $disk->put($fileName, $svg);

            $uploadId = DB::table('uploads')->insertGetId([
                'file_original_name' => $preset->key() . '-' . $spec['slot'],
                'file_name'          => $fileName,
                'user_id'            => $adminUserId,
                'file_size'          => strlen($svg),
                'extension'          => 'svg',
                'type'               => 'image',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $map[$spec['slot']] = $uploadId;
        }

        return $map;
    }

    private function svg(string $hex, string $title, string $tagline): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES);
        $tagline = htmlspecialchars($tagline, ENT_QUOTES);
        $dark = $this->darken($hex);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="400" viewBox="0 0 1200 400">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$hex}"/>
      <stop offset="100%" stop-color="{$dark}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="400" fill="url(#g)"/>
  <text x="80" y="190" font-family="Arial, sans-serif" font-size="64" font-weight="700" fill="#ffffff">{$title}</text>
  <text x="80" y="250" font-family="Arial, sans-serif" font-size="30" fill="#ffffff" opacity="0.9">{$tagline}</text>
</svg>
SVG;
    }

    private function darken(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, (int) hexdec(substr($hex, 0, 2)) - 40);
        $g = max(0, (int) hexdec(substr($hex, 2, 2)) - 40);
        $b = max(0, (int) hexdec(substr($hex, 4, 2)) - 40);
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `shop-test --filter=BannerGeneratorTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Themes/BannerGenerator.php codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "feat(shop/theme): SVG banner generator"
```

---

## Phase D — ThemeApplier (core engine)

The applier is built up across Tasks 5–8: look-only apply, then reset/restore, then demo seeding, then switching/fail-safe. Each task adds tests and the matching code.

### Task 5: `ThemeApplier::apply()` — look only (settings + tracking)

**Files:**
- Create: `<shop>/app/Themes/ThemeApplier.php`
- Test: `<shop>/tests/Theme/ThemeApplierLookTest.php`

`ThemeApplier` public API (used by later tasks and the controller):
- `__construct(BannerGenerator $banners)`
- `apply(string $vertical, bool $loadDemo, int $adminUserId = 1, int $adminShopId = 1): ThemeApplication`
- `reset(): void`
- `activeApplication(): ?ThemeApplication`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Theme/ThemeApplierLookTest.php
namespace Tests\Theme;

use App\Themes\ThemeApplier;
use App\Themes\ThemeApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ThemeApplierLookTest extends ThemeTestCase
{
    public function test_apply_writes_look_settings_and_records_them(): void
    {
        Storage::fake('public');
        // pre-existing base_color so we can assert prior_value capture
        DB::table('settings')->insert(['type' => 'base_color', 'value' => '#000000', 'created_at' => now(), 'updated_at' => now()]);

        $app = app(ThemeApplier::class)->apply('electronics', loadDemo: false);

        $this->assertInstanceOf(ThemeApplication::class, $app);
        $this->assertSame('electronics', $app->vertical);
        $this->assertFalse($app->demo_loaded);

        // base_color updated to the preset color
        $this->assertSame('#2563EB', DB::table('settings')->where('type', 'base_color')->value('value'));
        // section titles written
        $this->assertSame('Featured', DB::table('settings')->where('type', 'home_product_section_1_title')->value('value'));
        // banner slot now points at an uploads id (numeric)
        $sliderVal = DB::table('settings')->where('type', 'home_slider_1_img')->value('value');
        $this->assertTrue(ctype_digit((string) $sliderVal));

        // prior value of base_color captured for restore
        $priorItem = DB::table('theme_application_items')
            ->where('kind', 'setting')->where('setting_type', 'base_color')->first();
        $this->assertSame('#000000', $priorItem->prior_value);

        // no demo catalog when loadDemo=false
        $this->assertSame(0, DB::table('products')->count());
        $this->assertSame(0, DB::table('categories')->count());

        $this->assertNotNull(app(ThemeApplier::class)->activeApplication());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=ThemeApplierLookTest`
Expected: FAIL ("Class App\Themes\ThemeApplier not found").

- [ ] **Step 3: Write the implementation (look-only; demo + rollback are stubs filled in Tasks 6–7)**

```php
<?php
// <shop>/app/Themes/ThemeApplier.php
namespace App\Themes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThemeApplier
{
    public function __construct(private BannerGenerator $banners) {}

    public function activeApplication(): ?ThemeApplication
    {
        return ThemeApplication::latest('id')->first();
    }

    public function apply(string $vertical, bool $loadDemo, int $adminUserId = 1, int $adminShopId = 1): ThemeApplication
    {
        $preset = ThemePreset::for($vertical);

        return DB::transaction(function () use ($preset, $vertical, $loadDemo, $adminUserId, $adminShopId) {
            $this->rollback($this->activeApplication());

            $app = ThemeApplication::create([
                'vertical'    => $vertical,
                'demo_loaded' => $loadDemo,
                'applied_at'  => now(),
            ]);

            // 1) generate banners -> upload ids
            $bannerMap = $this->banners->generate($preset, $adminUserId);
            foreach ($bannerMap as $uploadId) {
                $this->recordItem($app, 'upload', $uploadId);
            }

            // 2) write look settings (capturing prior values)
            $settings = ['base_color' => $preset->baseColor()];
            foreach ($bannerMap as $slot => $uploadId) {
                $settings[$slot] = (string) $uploadId;
            }
            foreach (array_values($preset->sectionTitles()) as $i => $title) {
                $settings['home_product_section_' . ($i + 1) . '_title'] = $title;
            }

            // 3) optional demo catalog (filled in Task 7); returns product ids to feature
            $productIds = $loadDemo ? $this->seedDemo($app, $preset, $adminShopId) : [];
            foreach (array_values($preset->sectionTitles()) as $i => $title) {
                $settings['home_product_section_' . ($i + 1) . '_products'] = json_encode(
                    array_map('strval', $productIds)
                );
            }

            foreach ($settings as $type => $value) {
                $this->writeSetting($app, $type, $value);
            }

            Cache::forget('settings');
            return $app;
        });
    }

    public function reset(): void
    {
        DB::transaction(function () {
            $this->rollback($this->activeApplication());
            Cache::forget('settings');
        });
    }

    private function writeSetting(ThemeApplication $app, string $type, ?string $value): void
    {
        $existing = DB::table('settings')->where('type', $type)->first();
        $this->recordSetting($app, $type, $existing->value ?? null);

        DB::table('settings')->updateOrInsert(
            ['type' => $type],
            ['value' => $value, 'updated_at' => now(), 'created_at' => $existing->created_at ?? now()]
        );
    }

    private function recordSetting(ThemeApplication $app, string $type, ?string $priorValue): void
    {
        ThemeApplicationItem::create([
            'theme_application_id' => $app->id,
            'kind'                 => 'setting',
            'setting_type'         => $type,
            'prior_value'          => $priorValue,
        ]);
    }

    private function recordItem(ThemeApplication $app, string $kind, int $refId): void
    {
        ThemeApplicationItem::create([
            'theme_application_id' => $app->id,
            'kind'                 => $kind,
            'ref_id'               => $refId,
        ]);
    }

    /** Filled in Task 7. Returns seeded product ids. @return int[] */
    private function seedDemo(ThemeApplication $app, ThemePreset $preset, int $adminShopId): array
    {
        return [];
    }

    /** Filled in Task 6. */
    private function rollback(?ThemeApplication $app): void
    {
        // no-op until Task 6
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `shop-test --filter=ThemeApplierLookTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Themes/ThemeApplier.php codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "feat(shop/theme): ThemeApplier look-only apply + setting tracking"
```

---

### Task 6: `rollback()` — restore settings + delete tagged items

**Files:**
- Modify: `<shop>/app/Themes/ThemeApplier.php` (replace the `rollback()` stub)
- Test: `<shop>/tests/Theme/ThemeApplierResetTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Theme/ThemeApplierResetTest.php
namespace Tests\Theme;

use App\Themes\ThemeApplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ThemeApplierResetTest extends ThemeTestCase
{
    public function test_reset_restores_prior_settings_and_removes_theme_items(): void
    {
        Storage::fake('public');
        DB::table('settings')->insert(['type' => 'base_color', 'value' => '#111111', 'created_at' => now(), 'updated_at' => now()]);

        $applier = app(ThemeApplier::class);
        $applier->apply('pharmacy', loadDemo: false);

        // sanity: changed
        $this->assertSame('#0D9488', DB::table('settings')->where('type', 'base_color')->value('value'));
        $uploadCount = DB::table('uploads')->whereNull('deleted_at')->count();
        $this->assertGreaterThan(0, $uploadCount);

        $applier->reset();

        // prior base_color restored
        $this->assertSame('#111111', DB::table('settings')->where('type', 'base_color')->value('value'));
        // a setting that did NOT exist before (section title) is removed entirely
        $this->assertNull(DB::table('settings')->where('type', 'home_product_section_1_title')->value('value'));
        // generated uploads soft-deleted
        $this->assertSame(0, DB::table('uploads')->whereNull('deleted_at')->count());
        // application + items gone
        $this->assertSame(0, DB::table('theme_applications')->count());
        $this->assertSame(0, DB::table('theme_application_items')->count());
        $this->assertNull($applier->activeApplication());
    }

    public function test_reset_leaves_merchant_data_untouched(): void
    {
        Storage::fake('public');
        // merchant's own product + category + setting (not theme-tracked)
        $catId = DB::table('categories')->insertGetId(['parent_id' => 0, 'level' => 0, 'name' => 'My Cat', 'slug' => 'my-cat', 'created_at' => now(), 'updated_at' => now()]);
        $prodId = DB::table('products')->insertGetId(['shop_id' => 1, 'name' => 'My Product', 'slug' => 'my-product', 'main_category' => $catId, 'lowest_price' => 5, 'highest_price' => 5, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('settings')->insert(['type' => 'site_name', 'value' => 'My Store', 'created_at' => now(), 'updated_at' => now()]);

        $applier = app(ThemeApplier::class);
        $applier->apply('electronics', loadDemo: true);
        $applier->reset();

        $this->assertNotNull(DB::table('categories')->find($catId));
        $this->assertNotNull(DB::table('products')->find($prodId));
        $this->assertSame('My Store', DB::table('settings')->where('type', 'site_name')->value('value'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=ThemeApplierResetTest`
Expected: FAIL (prior settings not restored; uploads not deleted — rollback is still a no-op).

- [ ] **Step 3: Replace the `rollback()` stub**

In `<shop>/app/Themes/ThemeApplier.php`, replace the entire `rollback()` method with:

```php
    private function rollback(?ThemeApplication $app): void
    {
        if (! $app) {
            return;
        }

        foreach ($app->items()->get() as $item) {
            switch ($item->kind) {
                case 'setting':
                    if ($item->prior_value === null) {
                        DB::table('settings')->where('type', $item->setting_type)->delete();
                    } else {
                        DB::table('settings')->where('type', $item->setting_type)
                            ->update(['value' => $item->prior_value, 'updated_at' => now()]);
                    }
                    break;

                case 'upload':
                    $upload = DB::table('uploads')->find($item->ref_id);
                    if ($upload && $upload->file_name) {
                        Storage::disk('public')->delete($upload->file_name);
                    }
                    DB::table('uploads')->where('id', $item->ref_id)->update(['deleted_at' => now()]);
                    break;

                case 'category':
                    DB::table('categories')->where('id', $item->ref_id)->update(['deleted_at' => now()]);
                    DB::table('category_translations')->where('category_id', $item->ref_id)->delete();
                    break;

                case 'product':
                    DB::table('products')->where('id', $item->ref_id)->update(['deleted_at' => now()]);
                    DB::table('product_translations')->where('product_id', $item->ref_id)->update(['deleted_at' => now()]);
                    break;
            }
        }

        $app->items()->delete();
        $app->delete();
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `shop-test --filter=ThemeApplierResetTest`
Expected: PASS (both cases).

- [ ] **Step 5: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Themes/ThemeApplier.php codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "feat(shop/theme): reversible rollback (restore settings, remove tagged items)"
```

---

### Task 7: `seedDemo()` — tagged demo catalog

**Files:**
- Modify: `<shop>/app/Themes/ThemeApplier.php` (replace the `seedDemo()` stub)
- Test: `<shop>/tests/Theme/ThemeApplierDemoTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Theme/ThemeApplierDemoTest.php
namespace Tests\Theme;

use App\Themes\ThemeApplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ThemeApplierDemoTest extends ThemeTestCase
{
    public function test_apply_with_demo_seeds_tagged_catalog(): void
    {
        Storage::fake('public');

        $app = app(ThemeApplier::class)->apply('pet_shop', loadDemo: true, adminUserId: 1, adminShopId: 1);

        // categories created (4 parents + 4 children for pet_shop = 8)
        $this->assertSame(8, DB::table('categories')->whereNull('deleted_at')->count());
        // products created (5 for pet_shop)
        $this->assertSame(5, DB::table('products')->whereNull('deleted_at')->count());

        // products carry the admin shop id (storefront visibility)
        $this->assertSame(5, DB::table('products')->where('shop_id', 1)->count());

        // translations written for default locale
        $this->assertSame(8, DB::table('category_translations')->count());
        $this->assertSame(5, DB::table('product_translations')->whereNull('deleted_at')->count());

        // every catalog row is tracked for reversal
        $this->assertSame(8, DB::table('theme_application_items')->where('kind', 'category')->count());
        $this->assertSame(5, DB::table('theme_application_items')->where('kind', 'product')->count());

        // featured product ids wired into a home section
        $section = DB::table('settings')->where('type', 'home_product_section_1_products')->value('value');
        $ids = json_decode($section, true);
        $this->assertNotEmpty($ids);
        foreach ($ids as $id) {
            $this->assertNotNull(DB::table('products')->find((int) $id));
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=ThemeApplierDemoTest`
Expected: FAIL (0 categories/products — `seedDemo()` is still a stub).

- [ ] **Step 3: Replace the `seedDemo()` stub**

In `<shop>/app/Themes/ThemeApplier.php`, replace the entire `seedDemo()` method with:

```php
    /** @return int[] seeded product ids (to feature on the home page) */
    private function seedDemo(ThemeApplication $app, ThemePreset $preset, int $adminShopId): array
    {
        $lang = config('app.locale', 'en');
        $catalog = $preset->catalog();
        $catIdByName = [];

        // categories: parents first (level 0), then children (level 1)
        foreach ($catalog['categories'] as $parent) {
            $parentId = $this->createCategory($app, $parent['name'], 0, 0, $lang);
            $catIdByName[$parent['name']] = $parentId;

            foreach ($parent['children'] ?? [] as $childName) {
                $childId = $this->createCategory($app, $childName, $parentId, 1, $lang);
                $catIdByName[$childName] = $childId;
            }
        }

        // products
        $productIds = [];
        foreach ($catalog['products'] as $p) {
            $categoryId = $catIdByName[$p['category']] ?? 0;
            $slug = Str::slug($p['name']) . '-' . Str::random(6);

            $productId = DB::table('products')->insertGetId([
                'shop_id'       => $adminShopId,
                'name'          => $p['name'],
                'slug'          => $slug,
                'published'     => 1,
                'approved'      => 1,
                'main_category' => $categoryId,
                'lowest_price'  => $p['price'],
                'highest_price' => $p['price'],
                'unit'          => 'pc',
                'stock'         => 100,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::table('product_translations')->insert([
                'product_id' => $productId,
                'name'       => $p['name'],
                'unit'       => 'pc',
                'lang'       => $lang,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->recordItem($app, 'product', $productId);
            $productIds[] = $productId;
        }

        return $productIds;
    }

    private function createCategory(ThemeApplication $app, string $name, int $parentId, int $level, string $lang): int
    {
        $id = DB::table('categories')->insertGetId([
            'parent_id'   => $parentId,
            'level'       => $level,
            'name'        => $name,
            'order_level' => 0,
            'featured'    => 0,
            'slug'        => Str::slug($name) . '-' . Str::random(6),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('category_translations')->insert([
            'category_id' => $id,
            'name'        => $name,
            'lang'        => $lang,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->recordItem($app, 'category', $id);
        return $id;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `shop-test --filter=ThemeApplierDemoTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Themes/ThemeApplier.php codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "feat(shop/theme): tagged demo catalog seeding"
```

---

### Task 8: Switching + idempotency + fail-safe

**Files:**
- Test: `<shop>/tests/Theme/ThemeApplierSwitchTest.php`

No new implementation — these tests verify the transaction + rollback behavior built in Tasks 5–7. If any fails, fix the applier.

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Theme/ThemeApplierSwitchTest.php
namespace Tests\Theme;

use App\Themes\ThemeApplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ThemeApplierSwitchTest extends ThemeTestCase
{
    public function test_switching_themes_is_clean(): void
    {
        Storage::fake('public');
        $applier = app(ThemeApplier::class);

        $applier->apply('pharmacy', loadDemo: true);
        $applier->apply('electronics', loadDemo: true);

        // only one active application
        $this->assertSame(1, DB::table('theme_applications')->count());
        $this->assertSame('electronics', $applier->activeApplication()->vertical);

        // only electronics' catalog remains (5 products), no pharmacy remnants
        $this->assertSame(5, DB::table('products')->whereNull('deleted_at')->count());
        // electronics has 3 parents + 5 children = 8 categories
        $this->assertSame(8, DB::table('categories')->whereNull('deleted_at')->count());

        // color is electronics'
        $this->assertSame('#2563EB', DB::table('settings')->where('type', 'base_color')->value('value'));
    }

    public function test_reapply_same_theme_is_idempotent(): void
    {
        Storage::fake('public');
        $applier = app(ThemeApplier::class);

        $applier->apply('supershop', loadDemo: true);
        $applier->apply('supershop', loadDemo: true);

        // supershop: 4 parents + 2 children = 6 categories, 5 products — no duplicates
        $this->assertSame(6, DB::table('categories')->whereNull('deleted_at')->count());
        $this->assertSame(5, DB::table('products')->whereNull('deleted_at')->count());
        // no orphan live uploads beyond the current application's banners (2 for supershop)
        $this->assertSame(2, DB::table('uploads')->whereNull('deleted_at')->count());
    }

    public function test_failure_midway_leaves_current_theme_intact(): void
    {
        Storage::fake('public');
        $applier = app(ThemeApplier::class);
        $applier->apply('pharmacy', loadDemo: false);

        // Wrap the apply in an outer transaction that throws after it runs.
        // Laravel nests via savepoints, so the outer rollback discards the
        // entire apply — proving nothing is half-committed on downstream failure.
        try {
            DB::transaction(function () use ($applier) {
                $applier->apply('electronics', loadDemo: true);
                throw new \RuntimeException('boom'); // simulate downstream failure
            });
        } catch (\RuntimeException $e) {
            // expected
        }

        // still on pharmacy; no electronics leakage
        $this->assertSame('pharmacy', $applier->activeApplication()->vertical);
        $this->assertSame('#0D9488', DB::table('settings')->where('type', 'base_color')->value('value'));
        $this->assertSame(0, DB::table('products')->whereNull('deleted_at')->count());
    }
}
```

- [ ] **Step 2: Run test to verify it passes (or reveals a bug)**

Run: `shop-test --filter=ThemeApplierSwitchTest`
Expected: PASS. If `test_failure_midway...` fails, ensure `apply()` does all its work inside the single `DB::transaction` (it does in Task 5) so an outer rollback discards it.

- [ ] **Step 3: Run the full theme suite**

Run: `shop-test --filter=Theme`
Expected: all theme tests green.

- [ ] **Step 4: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "test(shop/theme): switching, idempotency, fail-safe"
```

---

## Phase E — Admin UI

### Task 9: `ThemeController` + routes

**Files:**
- Create: `<shop>/app/Http/Controllers/Admin/ThemeController.php`
- Modify: `<shop>/routes/admin.php`
- Test: `<shop>/tests/Theme/ThemeControllerTest.php`

- [ ] **Step 1: Write the failing test**

Tests call the controller directly (like the agent UI test) to avoid the full admin-auth stack.

```php
<?php
// <shop>/tests/Theme/ThemeControllerTest.php
namespace Tests\Theme;

use App\Http\Controllers\Admin\ThemeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ThemeControllerTest extends ThemeTestCase
{
    public function test_apply_action_applies_theme_with_demo(): void
    {
        Storage::fake('public');
        $controller = app(ThemeController::class);

        $request = Request::create('/admin/theme/apply', 'POST', [
            'vertical' => 'pharmacy',
            'load_demo' => '1',
        ]);

        $controller->apply($request, app(\App\Themes\ThemeApplier::class));

        $this->assertSame('#0D9488', DB::table('settings')->where('type', 'base_color')->value('value'));
        $this->assertGreaterThan(0, DB::table('products')->count());
    }

    public function test_apply_rejects_unknown_vertical(): void
    {
        Storage::fake('public');
        $controller = app(ThemeController::class);
        $request = Request::create('/admin/theme/apply', 'POST', ['vertical' => 'spaceships']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->apply($request, app(\App\Themes\ThemeApplier::class));
    }

    public function test_reset_clears_active_theme(): void
    {
        Storage::fake('public');
        $applier = app(\App\Themes\ThemeApplier::class);
        $applier->apply('electronics', loadDemo: false);

        app(ThemeController::class)->reset($applier);

        $this->assertNull($applier->activeApplication());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=ThemeControllerTest`
Expected: FAIL ("Class App\Http\Controllers\Admin\ThemeController not found").

- [ ] **Step 3: Write the controller**

```php
<?php
// <shop>/app/Http/Controllers/Admin/ThemeController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Themes\ThemeApplier;
use App\Themes\ThemePreset;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function index(ThemeApplier $applier)
    {
        return view('backend.theme.index', [
            'presets' => ThemePreset::all(),
            'active'  => $applier->activeApplication(),
        ]);
    }

    public function apply(Request $request, ThemeApplier $applier)
    {
        $data = $request->validate([
            'vertical'  => 'required|in:electronics,supershop,pharmacy,pet_shop',
            'load_demo' => 'nullable',
        ]);

        $applier->apply($data['vertical'], loadDemo: (bool) $request->boolean('load_demo'));

        return redirect()->back()->with('success', 'Theme applied.');
    }

    public function reset(ThemeApplier $applier)
    {
        $applier->reset();
        return redirect()->back()->with('success', 'Theme reset to default look.');
    }
}
```

- [ ] **Step 4: Add routes**

In `<shop>/routes/admin.php`, find the standalone admin group that holds the agent routes (the one commented `// Agent routes - admin only but NOT subject to agent.enforce`, around line 352). Add the theme routes **inside that same group** so the theme page stays reachable even when the admin is locked:

```php
use App\Http\Controllers\Admin\ThemeController;

Route::get('theme', [ThemeController::class, 'index'])->name('theme.index');
Route::post('theme/apply', [ThemeController::class, 'apply'])->name('theme.apply');
Route::post('theme/reset', [ThemeController::class, 'reset'])->name('theme.reset');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `shop-test --filter=ThemeControllerTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Http/Controllers/Admin/ThemeController.php \
        codecanyon-34858541-the-shop/install/routes/admin.php \
        codecanyon-34858541-the-shop/install/tests/Theme
git commit -m "feat(shop/theme): admin ThemeController + routes"
```

---

### Task 10: Admin view + sidebar menu link

**Files:**
- Create: `<shop>/resources/views/backend/theme/index.blade.php`
- Modify: `<shop>/resources/views/backend/inc/admin_sidenav.blade.php`

No automated test (Blade view); verified in the Phase F smoke test.

- [ ] **Step 1: Write the view**

```blade
{{-- <shop>/resources/views/backend/theme/index.blade.php --}}
@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-3">
    <h1 class="h3">Store Theme</h1>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@if($active)
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>Active theme: <strong>{{ ucwords(str_replace('_', ' ', $active->vertical)) }}</strong>
            @if($active->demo_loaded) <span class="badge badge-soft-primary">demo catalog loaded</span> @endif</span>
        <form method="POST" action="{{ route('theme.reset') }}" onsubmit="return confirm('Reset to default look and remove this theme\'s demo data?');">
            @csrf
            <button class="btn btn-sm btn-outline-danger" type="submit">Reset to default look</button>
        </form>
    </div>
@endif

<div class="row">
    @foreach($presets as $preset)
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div style="height:90px;background:{{ $preset->baseColor() }};border-top-left-radius:.5rem;border-top-right-radius:.5rem;"></div>
            <div class="card-body">
                <h5 class="mb-1">{{ $preset->label() }}</h5>
                <p class="text-muted mb-2"><small>{{ implode(' · ', $preset->sectionTitles()) }}</small></p>
                <div class="d-flex align-items-center mb-3">
                    <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $preset->baseColor() }};margin-right:8px;"></span>
                    <code>{{ $preset->baseColor() }}</code>
                </div>
                <form method="POST" action="{{ route('theme.apply') }}"
                      onsubmit="return confirm('Apply the {{ $preset->label() }} theme?');">
                    @csrf
                    <input type="hidden" name="vertical" value="{{ $preset->key() }}">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="demo_{{ $preset->key() }}" name="load_demo" value="1">
                        <label class="custom-control-label" for="demo_{{ $preset->key() }}">Also load sample catalog</label>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Apply</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
```

- [ ] **Step 2: Add the sidebar menu link**

In `<shop>/resources/views/backend/inc/admin_sidenav.blade.php`, find an appearance/website-setup section (search for an existing `route('website.` or "Setup" / "Appearance" menu item) and add a sibling link. Use the same markup style as the surrounding items; the link is:

```blade
<li class="aiz-side-nav-item">
    <a href="{{ route('theme.index') }}"
       class="aiz-side-nav-link {{ areActiveRoutes(['theme.index'], 'active') }}">
        <span class="aiz-side-nav-text">Store Theme</span>
    </a>
</li>
```

(If the file uses a different active-route helper than `areActiveRoutes`, match whatever the neighboring items use. `areActiveRoutes` is the helper The Shop ships for sidebar active state.)

- [ ] **Step 3: Verify the view renders without a Blade syntax error**

Run: `shop-art view:clear` then load `/admin/theme` in the Phase F smoke. (No unit test for Blade.)

- [ ] **Step 4: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/resources/views/backend/theme \
        codecanyon-34858541-the-shop/install/resources/views/backend/inc/admin_sidenav.blade.php
git commit -m "feat(shop/theme): admin Store Theme page + sidebar link"
```

---

## Phase F — Migrate + manual smoke

### Task 11: Run migrations + end-to-end smoke in the live store

**Files:**
- Create: `docs/superpowers/plans/store-industry-themes-smoke.md` (record results)

- [ ] **Step 1: Run the new migrations against the live store DB**

```bash
cd /home/tanmoy/Projects/Shop/theshop/codecanyon-34858541-the-shop/install
docker compose exec -T app php artisan migrate --force
```

Expected: `theme_applications` and `theme_application_items` tables created.

- [ ] **Step 2: Apply a theme from the admin UI**

Log into the store admin (`admin@example.com` / `password`), open **Store Theme** in the sidebar, pick **Pharmacy**, tick **Also load sample catalog**, click **Apply**. Confirm the success banner and "Active theme: Pharmacy".

- [ ] **Step 3: Verify the storefront reflects it**

Open the storefront home (`http://localhost:8000`). Confirm the primary color is teal, the generated Pharmacy banner shows in the slider, the home sections read "Medicines / Wellness / Personal Care", and demo products appear. (Hard-refresh; the `settings` cache was cleared by the applier.)

- [ ] **Step 4: Switch + reset**

Switch to **Electronics** (with demo) and confirm the home recolors to blue and only electronics demo products remain (no pharmacy remnants). Then **Reset to default look** and confirm the store returns to its pre-theme look with the demo catalog removed.

- [ ] **Step 5: Verify merchant data safety (spot check)**

```bash
docker exec theshop-db mysql -uroot -psecret shop -e \
 "SELECT COUNT(*) live_categories FROM categories WHERE deleted_at IS NULL; \
  SELECT COUNT(*) live_products FROM products WHERE deleted_at IS NULL;" 2>/dev/null
```

Confirm counts match the pre-test baseline (the store's original demo catalog is intact; only theme-seeded rows were added/removed).

- [ ] **Step 6: Record results + commit**

Write pass/fail notes into `docs/superpowers/plans/store-industry-themes-smoke.md` and:

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add docs/superpowers/plans/store-industry-themes-smoke.md
git commit -m "test(shop/theme): industry themes end-to-end smoke results"
```

---

## Notes for the Implementer

- **No vendor-table schema changes:** only `theme_applications` + `theme_application_items` are added. Demo content is tagged via those tables, never via a column on `categories`/`products`.
- **Reversibility is the core guarantee:** rollback walks only tracked item IDs, so merchant data is never touched. Keep every created/overwritten artifact recorded.
- **Cache:** always `Cache::forget('settings')` after writing settings, or the SPA/admin will read stale values.
- **Storefront visibility:** demo products must carry `shop_id` = the admin shop id (`get_setting('admin.shop_id')` equivalent; the live default is shop id 1) to pass `published_shops_ids()`. In tests we pass `adminShopId: 1`.
- **Idempotency / switching:** `apply()` always calls `rollback(activeApplication())` first inside the same transaction, so re-applying or switching never duplicates or orphans.
- **Deferred (future):** central super-admin assigning an industry per client; per-vertical fonts/layout variants; photographic banners; locales beyond the default app locale.
```