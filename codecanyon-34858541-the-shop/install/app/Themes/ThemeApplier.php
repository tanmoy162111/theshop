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

    /** @return int[] seeded product ids (to feature on the home page) */
    private function seedDemo(ThemeApplication $app, ThemePreset $preset, int $adminShopId): array
    {
        $lang = config('app.locale', 'en');
        $catalog = $preset->catalog();
        $catIdByName = [];

        foreach ($catalog['categories'] as $parent) {
            $parentId = $this->createCategory($app, $parent['name'], 0, 0, $lang);
            $catIdByName[$parent['name']] = $parentId;

            foreach ($parent['children'] ?? [] as $childName) {
                $childId = $this->createCategory($app, $childName, $parentId, 1, $lang);
                $catIdByName[$childName] = $childId;
            }
        }

        $productIds = [];
        foreach ($catalog['products'] as $p) {
            $categoryId = $catIdByName[$p['category']] ?? 0;
            $slug = Str::slug($p['name']) . '-' . Str::random(6);

            $productId = DB::table('products')->insertGetId([
                'shop_id'       => $adminShopId,
                'name'          => $p['name'],
                'slug'          => $slug,
                'published'     => 1,
                'approved'      => 1,
                'main_category' => $categoryId,
                'lowest_price'  => $p['price'],
                'highest_price' => $p['price'],
                'unit'          => 'pc',
                'stock'         => 100,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::table('product_translations')->insert([
                'product_id' => $productId,
                'name'       => $p['name'],
                'unit'       => 'pc',
                'lang'       => $lang,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->recordItem($app, 'product', $productId);
            $productIds[] = $productId;
        }

        return $productIds;
    }

    private function createCategory(ThemeApplication $app, string $name, int $parentId, int $level, string $lang): int
    {
        $id = DB::table('categories')->insertGetId([
            'parent_id'   => $parentId,
            'level'       => $level,
            'name'        => $name,
            'order_level' => 0,
            'featured'    => 0,
            'slug'        => Str::slug($name) . '-' . Str::random(6),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('category_translations')->insert([
            'category_id' => $id,
            'name'        => $name,
            'lang'        => $lang,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->recordItem($app, 'category', $id);
        return $id;
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
