<?php
namespace Tests\Theme;

use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class ThemeTestCase extends TestCase
{
    /**
     * Boot the app the same way Tests\CreatesApplication does, but build the
     * one legacy table ("settings") that routes/offline_payment.php reads
     * at boot time (via get_setting()) BEFORE the framework boots service
     * providers. Without this, app boot fails under sqlite_testing because
     * that table doesn't exist yet — it's normally created by the Shop's
     * SQL installer dump, not a Laravel migration.
     *
     * We run every console-kernel bootstrapper except BootProviders first
     * (this registers, but does not boot, providers — so the "db" service
     * becomes available without yet triggering route registration), create
     * the table, then run BootProviders ourselves.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';

        $bootstrappers = array_filter(
            $this->consoleKernelBootstrappers($app),
            fn ($bootstrapper) => $bootstrapper !== BootProviders::class
        );

        $app->bootstrapWith($bootstrappers);

        $app->make('db')->connection()->getSchemaBuilder()->create('settings', function ($t) {
            $t->id();
            $t->string('type')->nullable();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        (new BootProviders())->bootstrap($app);

        return $app;
    }

    private function consoleKernelBootstrappers($app): array
    {
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

        $reflection = new \ReflectionClass($kernel);
        $property = $reflection->getProperty('bootstrappers');
        $property->setAccessible(true);

        return $property->getValue($kernel);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal schema the theme code depends on (isolated from the full store schema).
        // "settings" is already created in createApplication() before providers boot.
        Schema::create('uploads', function ($t) {
            $t->id();
            $t->string('file_original_name')->nullable();
            $t->string('file_name')->nullable();
            $t->integer('user_id')->nullable();
            $t->integer('file_size')->nullable();
            $t->string('extension', 10)->nullable();
            $t->string('type', 15)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('categories', function ($t) {
            $t->id();
            $t->integer('parent_id')->default(0);
            $t->integer('level')->default(0);
            $t->string('name');
            $t->integer('order_level')->default(0);
            $t->integer('banner')->nullable();
            $t->integer('icon')->nullable();
            $t->integer('featured')->default(0);
            $t->string('slug')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('category_translations', function ($t) {
            $t->id();
            $t->integer('category_id');
            $t->string('name');
            $t->string('lang')->nullable();
            $t->timestamps();
        });

        Schema::create('products', function ($t) {
            $t->id();
            $t->integer('shop_id')->nullable();
            $t->string('name');
            $t->string('slug');
            $t->integer('published')->default(1);
            $t->integer('approved')->default(1);
            $t->integer('main_category')->nullable();
            $t->decimal('lowest_price', 20, 2)->default(0);
            $t->decimal('highest_price', 20, 2)->default(0);
            $t->string('unit')->nullable();
            $t->integer('stock')->default(0);
            $t->integer('thumbnail_img')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('product_translations', function ($t) {
            $t->id();
            $t->integer('product_id');
            $t->string('name');
            $t->string('unit')->nullable();
            $t->text('description')->nullable();
            $t->string('lang')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('theme_applications', function ($t) {
            $t->id();
            $t->string('vertical');
            $t->boolean('demo_loaded')->default(false);
            $t->timestamp('applied_at')->nullable();
            $t->timestamps();
        });

        Schema::create('theme_application_items', function ($t) {
            $t->id();
            $t->unsignedBigInteger('theme_application_id');
            $t->string('kind');
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('setting_type')->nullable();
            $t->text('prior_value')->nullable();
            $t->timestamps();
        });
    }
}
