<?php
namespace Tests\Theme;

use Illuminate\Support\Facades\Schema;

class ThemeHarnessSmokeTest extends ThemeTestCase
{
    public function test_harness_boots_and_builds_tables(): void
    {
        $this->assertTrue(Schema::hasTable('settings'));
        $this->assertTrue(Schema::hasTable('uploads'));
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('category_translations'));
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('product_translations'));
        $this->assertTrue(Schema::hasTable('theme_applications'));
        $this->assertTrue(Schema::hasTable('theme_application_items'));
        $this->assertSame('sqlite_testing', config('database.default'));
    }
}
