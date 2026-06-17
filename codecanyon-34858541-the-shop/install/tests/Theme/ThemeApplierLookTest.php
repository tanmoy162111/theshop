<?php
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
        DB::table('settings')->insert(['type' => 'base_color', 'value' => '#000000', 'created_at' => now(), 'updated_at' => now()]);

        $app = app(ThemeApplier::class)->apply('electronics', loadDemo: false);

        $this->assertInstanceOf(ThemeApplication::class, $app);
        $this->assertSame('electronics', $app->vertical);
        $this->assertFalse($app->demo_loaded);

        $this->assertSame('#2563EB', DB::table('settings')->where('type', 'base_color')->value('value'));
        $this->assertSame('Featured', DB::table('settings')->where('type', 'home_product_section_1_title')->value('value'));
        $sliderVal = DB::table('settings')->where('type', 'home_slider_1_img')->value('value');
        $this->assertTrue(ctype_digit((string) $sliderVal));

        $priorItem = DB::table('theme_application_items')
            ->where('kind', 'setting')->where('setting_type', 'base_color')->first();
        $this->assertSame('#000000', $priorItem->prior_value);

        $this->assertSame(0, DB::table('products')->count());
        $this->assertSame(0, DB::table('categories')->count());

        $this->assertNotNull(app(ThemeApplier::class)->activeApplication());
    }
}
