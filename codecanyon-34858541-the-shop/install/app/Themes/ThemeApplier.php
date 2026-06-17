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

    private function rollback(?ThemeApplication $app): void
    {
        if (! $app) {
            return;
        }

        foreach ($app->items()->get() as $item) {
            switch ($item->kind) {
                case 'setting':
                    if ($item->prior_value === null) {
                        DB::table('settings')->where('type', $item->setting_type)->delete();
                    } else {
                        DB::table('settings')->where('type', $item->setting_type)
                            ->update(['value' => $item->prior_value, 'updated_at' => now()]);
                    }
                    break;

                case 'upload':
                    $upload = DB::table('uploads')->find($item->ref_id);
                    if ($upload && $upload->file_name) {
                        Storage::disk('public')->delete($upload->file_name);
                    }
                    DB::table('uploads')->where('id', $item->ref_id)->update(['deleted_at' => now()]);
                    break;

                case 'category':
                    DB::table('categories')->where('id', $item->ref_id)->update(['deleted_at' => now()]);
                    DB::table('category_translations')->where('category_id', $item->ref_id)->delete();
                    break;

                case 'product':
                    DB::table('products')->where('id', $item->ref_id)->update(['deleted_at' => now()]);
                    DB::table('product_translations')->where('product_id', $item->ref_id)->update(['deleted_at' => now()]);
                    break;
            }
        }

        $app->items()->delete();
        $app->delete();
    }
}
