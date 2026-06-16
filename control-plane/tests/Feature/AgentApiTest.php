<?php
namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_pending_client_and_returns_token(): void
    {
        $res = $this->postJson('/api/v1/agent/register', [
            'business_name' => 'Acme', 'contact_email' => 'a@acme.test',
            'domain' => 'acme.test', 'app_version' => '1.0.0',
        ])->assertCreated()->assertJson(['status' => 'pending']);

        $token = $res->json('token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('clients', [
            'primary_domain' => 'acme.test', 'status' => 'pending',
            'token' => hash('sha256', $token),
        ]);
    }

    public function test_register_duplicate_domain_returns_existing_status_without_new_token(): void
    {
        Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@acme.test',
            'primary_domain' => 'acme.test', 'token' => hash('sha256', 'orig'), 'status' => 'active',
        ]);

        $this->postJson('/api/v1/agent/register', [
            'business_name' => 'Acme', 'contact_email' => 'a@acme.test',
            'domain' => 'acme.test', 'app_version' => '1.0.0',
        ])->assertOk()->assertJson(['status' => 'active'])->assertJsonMissing(['token' => true]);
    }

    public function test_report_upserts_and_is_idempotent(): void
    {
        $c = $this->activeClient();

        $payload = [
            'period_start' => '2026-06-15', 'period_end' => '2026-06-15',
            'gross_sales' => 250.00, 'order_count' => 4, 'currency' => 'USD', 'app_version' => '1.0.0',
        ];
        $headers = ['Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test'];

        $this->postJson('/api/v1/agent/report', $payload, $headers)
             ->assertOk()->assertJson(['accepted' => true, 'commission_type' => 'percent']);
        // resend same period -> still one row
        $this->postJson('/api/v1/agent/report', array_merge($payload, ['gross_sales' => 999.00]), $headers)
             ->assertOk();

        $this->assertDatabaseCount('sales_reports', 1);
        $this->assertEquals(999.00, $c->reports()->first()->gross_sales);
        $this->assertNotNull($c->fresh()->last_report_at);
    }

    public function test_report_rejected_when_pending(): void
    {
        Client::create([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'), 'status' => 'pending',
        ]);
        $this->postJson('/api/v1/agent/report', [
            'period_start' => '2026-06-15', 'period_end' => '2026-06-15',
            'gross_sales' => 1, 'order_count' => 1, 'currency' => 'USD',
        ], ['Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test'])
          ->assertOk()->assertJson(['accepted' => false, 'status' => 'pending']);
        $this->assertDatabaseCount('sales_reports', 0);
    }

    public function test_status_endpoint_returns_config(): void
    {
        $this->activeClient(['commission_type' => 'per_order', 'commission_rate' => 3]);
        $this->getJson('/api/v1/agent/status', [
            'Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test',
        ])->assertOk()->assertJson([
            'status' => 'active', 'commission_type' => 'per_order', 'commission_rate' => '3.00',
        ]);
    }

    private function activeClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'), 'status' => 'active',
        ], $overrides));
    }
}
