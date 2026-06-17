<?php
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

        $this->assertSame('#0D9488', DB::table('settings')->where('type', 'base_color')->value('value'));
        $uploadCount = DB::table('uploads')->whereNull('deleted_at')->count();
        $this->assertGreaterThan(0, $uploadCount);

        $applier->reset();

        $this->assertSame('#111111', DB::table('settings')->where('type', 'base_color')->value('value'));
        $this->assertNull(DB::table('settings')->where('type', 'home_product_section_1_title')->value('value'));
        $this->assertSame(0, DB::table('uploads')->whereNull('deleted_at')->count());
        $this->assertSame(0, DB::table('theme_applications')->count());
        $this->assertSame(0, DB::table('theme_application_items')->count());
        $this->assertNull($applier->activeApplication());
    }

    public function test_reset_leaves_merchant_data_untouched(): void
    {
        Storage::fake('public');
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
