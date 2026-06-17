<?php
namespace App\Themes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThemeApplier
{
    public function __construct(private BannerGenerator $banners) {}

    public function activeApplication(): ?ThemeApplication
    {
        return ThemeApplication::latest('id')->first();
    }

    public function apply(string $vertical, bool $loadDemo, int $adminUserId = 1, int $adminShopId = 1): ThemeApplication
    {
        $preset = ThemePreset::for($vertical);

        return DB::transaction(function () use ($preset, $vertical, $loadDemo, $adminUserId, $adminShopId) {
            $this->rollback($this->activeApplication());

            $app = ThemeApplication::create([
                'vertical'    => $vertical,
                'demo_loaded' => $loadDemo,
                'applied_at'  => now(),
            ]);

            $bannerMap = $this->banners->generate($preset, $adminUserId);
            foreach ($bannerMap as $uploadId) {
                $this->recordItem($app, 'upload', $uploadId);
            }

            $settings = ['base_color' => $preset->baseColor()];
            foreach ($bannerMap as $slot => $uploadId) {
                $settings[$slot] = (string) $uploadId;
            }
            foreach (array_values($preset->sectionTitles()) as $i => $title) {
                $settings['home_product_section_' . ($i + 1) . '_title'] = $title;
            }

            $productIds = $loadDemo ? $this->seedDemo($app, $preset, $adminShopId) : [];
            foreach (array_values($preset->sectionTitles()) as $i => $title) {
                $settings['home_product_section_' . ($i + 1) . '_products'] = json_encode(
                    array_map('strval', $productIds)
                );
            }

            foreach ($settings as $type => $value) {
                $this->writeSetting($app, $type, $value);
            }

            Cache::forget('settings');
            return $app;
        });
    }

    public function reset(): void
    {
        DB::transaction(function () {
            $this->rollback($this->activeApplication());
            Cache::forget('settings');
        });
    }

    private function writeSetting(ThemeApplication $app, string $type, ?string $value): void
    {
        $existing = DB::table('settings')->where('type', $type)->first();
        $this->recordSetting($app, $type, $existing->value ?? null);

        DB::table('settings')->updateOrInsert(
            ['type' => $type],
            ['value' => $value, 'updated_at' => now(), 'created_at' => $existing->created_at ?? now()]
        );
    }

    private function recordSetting(ThemeApplication $app, string $type, ?string $priorValue): void
    {
        ThemeApplicationItem::create([
            'theme_application_id' => $app->id,
            'kind'                 => 'setting',
            'setting_type'         => $type,
            'prior_value'          => $priorValue,
        ]);
    }

    private function recordItem(ThemeApplication $app, string $kind, int $refId): void
    {
        ThemeApplicationItem::create([
            'theme_application_id' => $app->id,
            'kind'                 => $kind,
            'ref_id'               => $refId,
        ]);
    }

    /** Filled in Task 7. Returns seeded product ids. @return int[] */
    private function seedDemo(ThemeApplication $app, ThemePreset $preset, int $adminShopId): array
    {
        return [];
    }

    /** Filled in Task 6. */
    private function rollback(?ThemeApplication $app): void
    {
        // no-op until Task 6
    }
}
