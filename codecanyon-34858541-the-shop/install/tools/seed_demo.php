<?php
/**
 * One-off demo data seeder for "The Shop".
 *
 * Creates an in-house shop (single-vendor mode requires admin->shop_id), demo
 * categories + products with real stock images, and repoints the existing
 * home-page settings (sliders / popular categories / 6 product sections) at the
 * new IDs. Existing demo settings reference upload/product/category IDs that no
 * longer exist (clean install), which is why the storefront rendered broken.
 *
 * Run:  docker compose exec app php tools/seed_demo.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Shop;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariation;
use App\Models\Upload;
use Illuminate\Support\Str;

$adminId = User::where('user_type', 'admin')->value('id');
$uploadDir = public_path('uploads/all');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

/** Download an image to public/uploads/all and create an Upload row. Returns upload id or null. */
function img(string $seed, int $w, int $h, int $adminId): ?int
{
    $url = "https://picsum.photos/seed/" . rawurlencode($seed) . "/$w/$h";
    $ctx = stream_context_create(['http' => ['timeout' => 20, 'follow_location' => 1]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 1000) {
        fwrite(STDERR, "  ! image download failed: $seed\n");
        return null;
    }
    $name = 'demo-' . Str::slug($seed) . '-' . Str::random(6) . '.jpg';
    $rel  = 'uploads/all/' . $name;
    file_put_contents(public_path($rel), $data);

    $u = new Upload;
    $u->file_original_name = $name;
    $u->file_name = $rel;
    $u->user_id = $adminId;
    $u->extension = 'jpg';
    $u->type = 'image';
    $u->file_size = strlen($data);
    $u->save();
    return $u->id;
}

/** Generate a simple logo PNG locally (no network) and create an Upload row. */
function logo(int $adminId): ?int
{
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    $w = 320; $h = 90;
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, 255, 255, 255);
    $fg = imagecolorallocate($im, 229, 57, 53);   // red
    $dk = imagecolorallocate($im, 33, 33, 33);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);
    imagefilledrectangle($im, 0, 0, 14, $h, $fg);
    imagestring($im, 5, 30, 24, 'THE', $dk);
    imagestring($im, 5, 80, 24, 'SHOP', $fg);
    imagestring($im, 3, 30, 50, 'demo store', $dk);
    $name = 'demo-logo-' . Str::random(6) . '.png';
    $rel = 'uploads/all/' . $name;
    imagepng($im, public_path($rel));
    imagedestroy($im);

    $u = new Upload;
    $u->file_original_name = $name;
    $u->file_name = $rel;
    $u->user_id = $adminId;
    $u->extension = 'png';
    $u->type = 'image';
    $u->file_size = filesize(public_path($rel));
    $u->save();
    return $u->id;
}

function set_setting(string $key, $value): void
{
    $s = \App\Models\Setting::firstOrNew(['type' => $key]);
    $s->value = $value;
    $s->save();
}

echo "== 1. In-house shop ==\n";
$shop = Shop::where('user_id', $adminId)->first() ?: new Shop;
$shop->user_id = $adminId;
$shop->name = 'The Shop';
$shop->slug = 'the-shop';
$shop->approval = 1;
$shop->published = 1;
$shop->verification_status = 1;
$shop->address = '123 Market Street, Demo City';
$shop->phone = '+1 555 010 2030';
$shop->save();
$admin = User::find($adminId);
$admin->shop_id = $shop->id;
$admin->save();
echo "   shop id={$shop->id}, admin->shop_id set\n";

echo "== 2. Logo ==\n";
if ($logoId = logo($adminId)) {
    set_setting('header_logo', $logoId);
    set_setting('footer_logo', $logoId);
    echo "   logo upload id=$logoId\n";
}

echo "== 3. Categories ==\n";
$categoryDefs = [
    ['name' => 'Electronics',        'seed' => 'tech'],
    ['name' => 'Fashion',            'seed' => 'fashion'],
    ['name' => 'Home & Living',      'seed' => 'home'],
    ['name' => 'Sports & Outdoors',  'seed' => 'sport'],
    ['name' => 'Beauty & Health',    'seed' => 'beauty'],
];
$categoryIds = [];
foreach ($categoryDefs as $i => $c) {
    $cat = new Category;
    $cat->name = $c['name'];
    $cat->parent_id = 0;
    $cat->level = 0;
    $cat->order_level = $i;
    $cat->featured = 1;
    $cat->top = 1;
    $cat->slug = Str::slug($c['name']);
    $cat->icon = img('icon-' . $c['seed'], 300, 300, $adminId);
    $cat->banner = img('banner-' . $c['seed'], 1200, 400, $adminId);
    $cat->meta_title = $c['name'];
    $cat->save();

    $ct = new CategoryTranslation;
    $ct->category_id = $cat->id;
    $ct->name = $c['name'];
    $ct->lang = 'en';
    $ct->save();

    $categoryIds[$i] = $cat->id;
    echo "   [{$cat->id}] {$c['name']}\n";
}

echo "== 4. Products ==\n";
$catalog = [
    0 => ['Wireless Earbuds Pro', 'Smart Watch Series 5', '4K Action Camera', 'Bluetooth Speaker', 'Mechanical Keyboard'],
    1 => ['Classic Denim Jacket', "Men's Running Sneakers", 'Leather Crossbody Bag', 'Cotton Crew T-Shirt', 'Aviator Sunglasses'],
    2 => ['Ceramic Coffee Mug Set', 'Scented Soy Candle', 'Memory Foam Pillow', 'Stainless Cookware Set', 'LED Desk Lamp'],
    3 => ['Yoga Mat Premium', 'Insulated Water Bottle', 'Adjustable Dumbbell', 'Trail Running Shoes', 'Camping Tent 2P'],
    4 => ['Vitamin C Serum', 'Bamboo Toothbrush Set', 'Aroma Diffuser', 'Facial Cleanser Gel', 'Hair Care Kit'],
];
$prices = [29.99, 149.00, 89.50, 45.00, 79.99];
$productIdsByCat = [];
foreach ($catalog as $ci => $names) {
    $catId = $categoryIds[$ci];
    foreach ($names as $pi => $pname) {
        $price = $prices[$pi];
        $thumb = img('prod-' . $ci . '-' . $pi, 600, 600, $adminId);

        $p = new Product;
        $p->name = $pname;
        $p->shop_id = $shop->id;
        $p->unit = 'pcs';
        $p->min_qty = 1;
        $p->max_qty = 10;
        $p->thumbnail_img = $thumb;            // upload id
        $p->photos = (string) $thumb;          // gallery (single image for demo)
        $p->description = "<p>The <strong>$pname</strong> is a demo product seeded for preview. "
            . "Great build quality, everyday value, and fast shipping.</p>";
        $p->published = 1;
        $p->approved = 1;
        $p->is_variant = 0;
        $p->main_category = $catId;
        $p->lowest_price = $price;
        $p->highest_price = $price;
        $p->discount = ($pi % 2 === 0) ? 10 : 0;
        $p->discount_type = 'percent';
        $p->stock = 50;
        $p->rating = 4 + ($pi % 2) * 0.5;
        $p->meta_title = $pname;
        $p->meta_description = $pname . ' - demo product';
        $p->meta_image = $thumb;
        $p->slug = Str::slug($pname) . '-' . strtolower(Str::random(5));
        $p->save();

        $pt = new ProductTranslation;
        $pt->product_id = $p->id;
        $pt->name = $pname;
        $pt->unit = 'pcs';
        $pt->description = $p->description;
        $pt->lang = 'en';
        $pt->save();

        $p->categories()->sync([$catId]);

        $v = new ProductVariation;
        $v->product_id = $p->id;
        $v->sku = 'DEMO-' . $catId . '-' . ($pi + 1);
        $v->price = $price;
        $v->stock = 50;
        $v->save();

        $productIdsByCat[$ci][] = $p->id;
        echo "   [{$p->id}] $pname (\${$price})\n";
    }
}

echo "== 5. Home page settings ==\n";
$allIds = array_merge(...array_values($productIdsByCat));

// Sliders: use the 5 category banners as slide images.
$bannerUploadIds = Category::whereIn('id', $categoryIds)->pluck('banner')->filter()->values()->all();
$sliderImgs = array_slice($bannerUploadIds, 0, 3);
set_setting('home_slider_1_images', json_encode(array_map('strval', $sliderImgs)));
set_setting('home_slider_1_links', json_encode(array_fill(0, count($sliderImgs), '/all-categories')));
foreach ([2, 3, 4] as $n) {     // clear unused sliders that referenced dead uploads
    set_setting("home_slider_{$n}_images", '');
}

// Popular categories
set_setting('home_popular_categories', json_encode(array_map('strval', array_values($categoryIds))));

// 6 product sections mapped to our categories (section 6 reuses a mix).
$sections = [
    1 => ['Today\'s Deals',          array_slice($allIds, 0, 8)],
    2 => ['Featured Electronics',    $productIdsByCat[0]],
    3 => ['Fashion Picks',           $productIdsByCat[1]],
    4 => ['Best Selling',            array_slice(array_reverse($allIds), 0, 8)],
    5 => ['Home & Living',           $productIdsByCat[2]],
    6 => ['Sports & Beauty',         array_merge($productIdsByCat[3], $productIdsByCat[4])],
];
foreach ($sections as $n => [$title, $ids]) {
    set_setting("home_product_section_{$n}_title", $title);
    set_setting("home_product_section_{$n}_products", json_encode(array_map('strval', $ids)));
}
// Section 3 & 6 promo banners referenced dead uploads -> clear them.
set_setting('home_product_section_3_banner_img', '');
set_setting('home_product_section_6_banner_img', '');

// Clear promo/shop banner sections that referenced dead uploads (avoid broken images).
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

echo "   sliders, popular categories, 6 product sections configured\n";

echo "== 6. Clear caches ==\n";
\Illuminate\Support\Facades\Cache::flush();
echo "   cache flushed\n";

echo "\nDONE. categories=" . count($categoryIds) . " products=" . count($allIds) . "\n";
