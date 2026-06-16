<?php
namespace Tests\Agent;

use App\Agent\AgentConfig;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

class AgentEnforcementTest extends AgentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The live app's routes/web.php registers a catch-all
        // (`{slug}` -> HomeController) at boot time, before this setUp()
        // runs. Since Laravel's router matches routes in registration
        // order, that catch-all would swallow any probe path added here.
        // Swap in a fresh, empty route collection so only our probes
        // exist for this test — no production routes are touched.
        app('router')->setRoutes(new RouteCollection);

        Route::middleware('agent.enforce:admin')->get('/_admin_probe', fn () => 'ADMIN_OK');
        Route::middleware('agent.enforce:storefront')->get('/_store_probe', fn () => 'STORE_OK');
    }

    public function test_locked_admin_blocks_admin_but_not_storefront(): void
    {
        app(AgentConfig::class)->set('status', 'locked_admin');

        $this->get('/_admin_probe')->assertStatus(503);
        $this->get('/_store_probe')->assertOk()->assertSee('STORE_OK');
    }

    public function test_maintenance_blocks_storefront(): void
    {
        app(AgentConfig::class)->set('status', 'maintenance');
        $this->get('/_store_probe')->assertStatus(503);
    }

    public function test_active_allows_everything(): void
    {
        app(AgentConfig::class)->set('status', 'active');
        $this->get('/_admin_probe')->assertOk();
        $this->get('/_store_probe')->assertOk();
    }

    public function test_warning_allows_but_flags_banner(): void
    {
        app(AgentConfig::class)->set('status', 'warning');
        app(AgentConfig::class)->set('status_message', 'overdue');
        $this->get('/_admin_probe')->assertOk();
        // banner exposed via shared view data
        $this->assertSame('overdue', view()->shared('agent_banner'));
    }
}
