<?php
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
