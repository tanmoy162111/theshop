<?php
namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AgentAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware('agent.auth')->get('/_probe', fn () => response()->json([
            'client_id' => request()->attributes->get('agent_client')->id,
        ]));
    }

    public function test_rejects_missing_token(): void
    {
        $this->getJson('/_probe')->assertStatus(401);
    }

    public function test_rejects_domain_mismatch(): void
    {
        Client::create([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'),
        ]);
        $this->getJson('/_probe', [
            'Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'evil.test',
        ])->assertStatus(403);
    }

    public function test_accepts_valid_token_and_domain(): void
    {
        $c = Client::create([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'),
        ]);
        $this->getJson('/_probe', [
            'Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test',
        ])->assertOk()->assertJson(['client_id' => $c->id]);

        $this->assertNotNull($c->fresh()->last_seen_at);
    }
}
