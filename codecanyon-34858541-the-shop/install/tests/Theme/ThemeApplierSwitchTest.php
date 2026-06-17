<?php
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

        $this->assertSame(1, DB::table('theme_applications')->count());
        $this->assertSame('electronics', $applier->activeApplication()->vertical);

        $this->assertSame(5, DB::table('products')->whereNull('deleted_at')->count());
        $this->assertSame(8, DB::table('categories')->whereNull('deleted_at')->count());

        $this->assertSame('#2563EB', DB::table('settings')->where('type', 'base_color')->value('value'));
    }

    public function test_reapply_same_theme_is_idempotent(): void
    {
        Storage::fake('public');
        $applier = app(ThemeApplier::class);

        $applier->apply('supershop', loadDemo: true);
        $applier->apply('supershop', loadDemo: true);

        $this->assertSame(6, DB::table('categories')->whereNull('deleted_at')->count());
        $this->assertSame(5, DB::table('products')->whereNull('deleted_at')->count());
        $this->assertSame(2, DB::table('uploads')->whereNull('deleted_at')->count());
    }

    public function test_failure_midway_leaves_current_theme_intact(): void
    {
        Storage::fake('public');
        $applier = app(ThemeApplier::class);
        $applier->apply('pharmacy', loadDemo: false);

        try {
            DB::transaction(function () use ($applier) {
                $applier->apply('electronics', loadDemo: true);
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame('pharmacy', $applier->activeApplication()->vertical);
        $this->assertSame('#0D9488', DB::table('settings')->where('type', 'base_color')->value('value'));
        $this->assertSame(0, DB::table('products')->whereNull('deleted_at')->count());
    }
}
