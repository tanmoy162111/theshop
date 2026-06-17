<?php
/**
 * Configure home-page settings to point at the demo data created by
 * seed_demo.php. Safe to re-run (only touches settings + cache, creates no rows).
 *
 * Run:  docker compose exec app php tools/seed_settings.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Setting;

/** Update-or-insert a setting without mass assignment (Setting is fully guarded). */
function set_setting(string $key, $value): void
{
    $s = Setting::where('type', $key)->first() ?: new Setting;
    $s->type = $key;
    $s->value = $value;
    $s->save();
}

$categoryIds = [1, 2, 3, 4, 5];
$productIdsByCat = [
    0 => [1, 2, 3, 4, 5],      // Electronics
    1 => [6, 7, 8, 9, 10],     // Fashion
    2 => [11, 12, 13, 14, 15], // Home & Living
    3 => [16, 17, 18, 19, 20], // Sports & Outdoors
    4 => [21, 22, 23, 24, 25], // Beauty & Health
];
$allIds = array_merge(...array_values($productIdsByCat));

// Sliders: reuse the category banners as slide images.
$bannerUploadIds = Category::whereIn('id', $categoryIds)->pluck('banner')->filter()->values()->all();
$sliderImgs = array_slice($bannerUploadIds, 0, 3);
set_setting('home_slider_1_images', json_encode(array_map('strval', $sliderImgs)));
set_setting('home_slider_1_links', json_encode(array_fill(0, count($sliderImgs), '/all-categories')));
foreach ([2, 3, 4] as $n) {
    set_setting("home_slider_{$n}_images", '');
}

// Popular categories
set_setting('home_popular_categories', json_encode(array_map('strval', $categoryIds)));

// 6 product sections
$sections = [
    1 => ['Today\'s Deals',       array_slice($allIds, 0, 8)],
    2 => ['Featured Electronics', $productIdsByCat[0]],
    3 => ['Fashion Picks',        $productIdsByCat[1]],
    4 => ['Best Selling',         array_slice(array_reverse($allIds), 0, 8)],
    5 => ['Home & Living',        $productIdsByCat[2]],
    6 => ['Sports & Beauty',      array_merge($productIdsByCat[3], $productIdsByCat[4])],
];
foreach ($sections as $n => [$title, $ids]) {
    set_setting("home_product_section_{$n}_title", $title);
    set_setting("home_product_section_{$n}_products", json_encode(array_map('strval', $ids)));
}

// Clear image refs that pointed at dead uploads (avoid broken images on home).
set_setting('home_product_section_3_banner_img', '');
set_setting('home_product_section_6_banner_img', '');
foreach ([1, 2, 3, 4] as $n) {
    set_setting("home_banner_{$n}_images", '');
}
foreach ([1, 2, 3] as $n) {
    set_setting("home_shop_banner_{$n}_images", '');
}
foreach ([1, 2, 3, 4, 5] as $n) {
    set_setting("home_shop_section_{$n}_shops", '');
}
set_setting('topbar_banner', '');

\Illuminate\Support\Facades\Cache::flush();

echo "Settings configured. sliders=" . count($sliderImgs)
    . " popular_categories=" . count($categoryIds)
    . " sections=6  cache flushed\n";
