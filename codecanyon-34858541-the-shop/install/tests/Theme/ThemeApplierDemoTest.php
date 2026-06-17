<?php
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

        $this->assertSame(8, DB::table('categories')->whereNull('deleted_at')->count());
        $this->assertSame(5, DB::table('products')->whereNull('deleted_at')->count());
        $this->assertSame(5, DB::table('products')->where('shop_id', 1)->count());
        $this->assertSame(8, DB::table('category_translations')->count());
        $this->assertSame(5, DB::table('product_translations')->whereNull('deleted_at')->count());
        $this->assertSame(8, DB::table('theme_application_items')->where('kind', 'category')->count());
        $this->assertSame(5, DB::table('theme_application_items')->where('kind', 'product')->count());

        $section = DB::table('settings')->where('type', 'home_product_section_1_products')->value('value');
        $ids = json_decode($section, true);
        $this->assertNotEmpty($ids);
        foreach ($ids as $id) {
            $this->assertNotNull(DB::table('products')->find((int) $id));
        }
    }
}
