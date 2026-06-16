<?php
namespace Tests\Agent;

use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class AgentTestCase extends TestCase
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

        // Minimal schema the agent depends on (isolated from the full store schema).
        Schema::create('agent_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('orders', function ($t) {
            $t->id();
            $t->decimal('grand_total', 20, 2)->default(0);
            $t->string('payment_status', 20)->default('unpaid');
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
    }
}
