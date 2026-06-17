<?php
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
