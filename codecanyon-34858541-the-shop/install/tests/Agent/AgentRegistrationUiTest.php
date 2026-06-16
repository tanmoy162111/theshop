<?php
namespace Tests\Agent;

use App\Agent\AgentConfig;
use Illuminate\Support\Facades\Http;

class AgentRegistrationUiTest extends AgentTestCase
{
    public function test_register_action_calls_central_and_stores_status(): void
    {
        Http::fake(['*/api/v1/agent/register' => Http::response([
            'client_id' => 9, 'token' => 'TKN', 'status' => 'pending',
        ], 201)]);

        // hit the controller action directly (avoids full admin-auth stack in this unit-style test)
        $controller = app(\App\Http\Controllers\Admin\AgentController::class);
        $request = \Illuminate\Http\Request::create('/admin/agent', 'POST', [
            'central_url' => 'https://central.test',
            'business_name' => 'Acme',
            'contact_email' => 'a@acme.test',
            'domain' => 'acme.test',
        ]);

        $controller->register($request, app(\App\Agent\AgentClient::class), app(AgentConfig::class));

        $config = app(AgentConfig::class);
        $this->assertSame('pending', $config->get('status'));
        $this->assertSame('TKN', $config->get('token'));
        $this->assertSame('https://central.test', $config->get('central_url'));
    }
}
