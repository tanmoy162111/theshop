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

    public function test_register_action_handles_central_failure_without_500(): void
    {
        Http::fake(['*/api/v1/agent/register' => Http::response(['message' => 'boom'], 500)]);

        $controller = app(\App\Http\Controllers\Admin\AgentController::class);
        $request = \Illuminate\Http\Request::create('/admin/agent', 'POST', [
            'central_url' => 'https://central.test',
            'business_name' => 'Acme',
            'contact_email' => 'a@acme.test',
            'domain' => 'acme.test',
        ]);

        // Must NOT throw, and must return a redirect (graceful handling).
        $response = $controller->register($request, app(\App\Agent\AgentClient::class), app(\App\Agent\AgentConfig::class));

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        // central_url is still stored (set before the failing call); registration did not complete
        $this->assertSame('https://central.test', app(\App\Agent\AgentConfig::class)->get('central_url'));
        $this->assertNotSame('pending', app(\App\Agent\AgentConfig::class)->get('status'));
    }
}
