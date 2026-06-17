<?php
namespace App\Themes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerGenerator
{
    /**
     * Generate one SVG banner per preset slot.
     *
     * @return array<string,int> slot => uploads.id
     */
    public function generate(ThemePreset $preset, int $adminUserId): array
    {
        $disk = Storage::disk('public');
        $map = [];

        foreach ($preset->banners() as $spec) {
            $svg = $this->svg($preset->baseColor(), $spec['title'], $spec['tagline']);
            $fileName = 'uploads/themes/' . $preset->key() . '-' . $spec['slot'] . '-' . Str::random(8) . '.svg';
            $disk->put($fileName, $svg);

            $uploadId = DB::table('uploads')->insertGetId([
                'file_original_name' => $preset->key() . '-' . $spec['slot'],
                'file_name'          => $fileName,
                'user_id'            => $adminUserId,
                'file_size'          => strlen($svg),
                'extension'          => 'svg',
                'type'               => 'image',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $map[$spec['slot']] = $uploadId;
        }

        return $map;
    }

    private function svg(string $hex, string $title, string $tagline): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES);
        $tagline = htmlspecialchars($tagline, ENT_QUOTES);
        $dark = $this->darken($hex);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="400" viewBox="0 0 1200 400">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$hex}"/>
      <stop offset="100%" stop-color="{$dark}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="400" fill="url(#g)"/>
  <text x="80" y="190" font-family="Arial, sans-serif" font-size="64" font-weight="700" fill="#ffffff">{$title}</text>
  <text x="80" y="250" font-family="Arial, sans-serif" font-size="30" fill="#ffffff" opacity="0.9">{$tagline}</text>
</svg>
SVG;
    }

    private function darken(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, (int) hexdec(substr($hex, 0, 2)) - 40);
        $g = max(0, (int) hexdec(substr($hex, 2, 2)) - 40);
        $b = max(0, (int) hexdec(substr($hex, 4, 2)) - 40);
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}
