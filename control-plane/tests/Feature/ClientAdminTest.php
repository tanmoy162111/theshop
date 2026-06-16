<?php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // Breeze layout uses @vite; no built assets in test env
    }

    public function test_guest_cannot_view_clients(): void
    {
        $this->get('/clients')->assertRedirect('/login');
    }

    public function test_admin_sees_clients_with_computed_commission(): void
    {
        $admin = User::factory()->create();
        $c = Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@a.test', 'primary_domain' => 'a.test',
            'token' => hash('sha256', 't'), 'status' => 'active',
            'commission_type' => 'percent', 'commission_rate' => 10,
        ]);
        $c->reports()->create([
            'period_start' => '2026-06-15', 'period_end' => '2026-06-15',
            'gross_sales' => 1000, 'order_count' => 5, 'currency' => 'USD', 'received_at' => now(),
        ]);

        $this->actingAs($admin)->get('/clients')
             ->assertOk()->assertSee('Acme')->assertSee('100.00'); // 10% of 1000
    }

    public function test_admin_can_approve_client(): void
    {
        $admin = User::factory()->create();
        $c = Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@a.test', 'primary_domain' => 'a.test',
            'token' => hash('sha256', 't'), 'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch("/clients/{$c->id}", ['action' => 'approve'])
             ->assertRedirect();
        $this->assertSame('active', $c->fresh()->status);
        $this->assertNotNull($c->fresh()->approved_at);
    }

    public function test_admin_can_set_commission_and_status(): void
    {
        $admin = User::factory()->create();
        $c = Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@a.test', 'primary_domain' => 'a.test',
            'token' => hash('sha256', 't'), 'status' => 'active',
        ]);

        $this->actingAs($admin)->patch("/clients/{$c->id}", [
            'action' => 'update',
            'commission_type' => 'per_order', 'commission_rate' => 2.5, 'status' => 'warning',
        ])->assertRedirect();

        $c->refresh();
        $this->assertSame('per_order', $c->commission_type);
        $this->assertEquals(2.5, $c->commission_rate);
        $this->assertSame('warning', $c->status);
    }
}
