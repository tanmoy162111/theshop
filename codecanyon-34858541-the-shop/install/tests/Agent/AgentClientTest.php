<?php
namespace Tests\Agent;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use Illuminate\Support\Facades\Http;

class AgentClientTest extends AgentTestCase
{
    public function test_register_stores_token_and_client_id(): void
    {
        Http::fake(['*/api/v1/agent/register' => Http::response([
            'client_id' => 7, 'token' => 'TKN', 'status' => 'pending',
        ], 201)]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');

        $status = app(AgentClient::class)->register('Acme', 'a@acme.test', 'acme.test');

        $this->assertSame('pending', $status);
        $this->assertSame('TKN', $config->get('token'));
        $this->assertSame('7', $config->get('client_id'));
    }

    public function test_sync_status_caches_config(): void
    {
        Http::fake(['*/api/v1/agent/status' => Http::response([
            'status' => 'warning', 'commission_type' => 'per_order',
            'commission_rate' => '3.00', 'message' => 'overdue', 'grace_until' => null,
        ], 200)]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');
        $config->set('token', 'TKN');

        app(AgentClient::class)->syncStatus();

        $this->assertSame('warning', $config->get('status'));
        $this->assertSame('per_order', $config->get('commission_type'));
        $this->assertSame('3.00', $config->get('commission_rate'));
        $this->assertSame('overdue', $config->get('status_message'));
    }

    public function test_sync_status_is_fail_open_on_network_error(): void
    {
        Http::fake(['*/api/v1/agent/status' => fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');
        $config->set('token', 'TKN');
        $config->set('status', 'active'); // last known

        app(AgentClient::class)->syncStatus(); // must not throw

        $this->assertSame('active', $config->get('status')); // unchanged
    }

    public function test_report_is_fail_open_on_network_error(): void
    {
        Http::fake(['*/api/v1/agent/report' => fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');
        $config->set('token', 'TKN');

        $result = app(AgentClient::class)->report(
            now()->startOfDay(), now()->endOfDay(), 100.50, 3, 'USD'
        );

        $this->assertFalse($result); // must not throw; must return false
    }
}
