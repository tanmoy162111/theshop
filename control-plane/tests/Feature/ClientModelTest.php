<?php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\SalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_has_reports_relation_and_defaults(): void
    {
        $client = Client::create([
            'business_name' => 'Acme Pharma',
            'contact_email' => 'a@acme.test',
            'primary_domain' => 'acme.test',
            'token' => hash('sha256', 'secret-token'),
        ]);

        $client->refresh(); // load DB defaults (status/commission_type/commission_rate)
        $this->assertSame('pending', $client->status);
        $this->assertSame('percent', $client->commission_type);
        $this->assertEquals(0, $client->commission_rate);

        $client->reports()->create([
            'period_start' => '2026-06-15',
            'period_end' => '2026-06-15',
            'gross_sales' => 100.50,
            'order_count' => 3,
            'currency' => 'USD',
            'received_at' => now(),
        ]);

        $this->assertCount(1, $client->reports);
        $this->assertInstanceOf(SalesReport::class, $client->reports->first());
    }
}
