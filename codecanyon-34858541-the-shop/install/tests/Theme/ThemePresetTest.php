<?php
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
