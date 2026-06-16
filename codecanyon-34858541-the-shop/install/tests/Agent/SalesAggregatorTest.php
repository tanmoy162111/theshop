<?php
namespace Tests\Agent;

use App\Agent\SalesAggregator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAggregatorTest extends AgentTestCase
{
    public function test_sums_only_paid_orders_in_period(): void
    {
        DB::table('orders')->insert([
            ['grand_total' => 100, 'payment_status' => 'paid',   'created_at' => '2026-06-15 10:00:00'],
            ['grand_total' => 50,  'payment_status' => 'paid',   'created_at' => '2026-06-15 23:59:59'],
            ['grand_total' => 999, 'payment_status' => 'unpaid', 'created_at' => '2026-06-15 12:00:00'],
            ['grand_total' => 777, 'payment_status' => 'paid',   'created_at' => '2026-06-16 00:00:01'], // out of range
        ]);

        $result = (new SalesAggregator)->forPeriod(
            Carbon::parse('2026-06-15 00:00:00'),
            Carbon::parse('2026-06-15 23:59:59')
        );

        $this->assertEquals(150.00, $result['gross_sales']);
        $this->assertSame(2, $result['order_count']);
    }
}
