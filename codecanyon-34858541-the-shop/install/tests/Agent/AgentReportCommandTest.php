<?php
namespace Tests\Agent;

use App\Agent\AgentConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AgentReportCommandTest extends AgentTestCase
{
    public function test_report_command_sends_yesterdays_paid_sales(): void
    {
        Http::fake(['*/api/v1/agent/report' => Http::response([
            'accepted' => true, 'status' => 'active', 'commission_type' => 'percent', 'commission_rate' => '10.00',
        ], 200)]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');
        $config->set('token', 'TKN');
        $config->set('domain', 'a.test');

        $yesterday = now()->subDay()->toDateString();
        DB::table('orders')->insert([
            ['grand_total' => 200, 'payment_status' => 'paid', 'created_at' => $yesterday . ' 09:00:00'],
        ]);

        $this->artisan('agent:report')->assertSuccessful();

        Http::assertSent(function ($request) use ($yesterday) {
            return str_contains($request->url(), '/agent/report')
                && $request['gross_sales'] == 200
                && $request['order_count'] == 1
                && $request['period_start'] === $yesterday;
        });
    }

    public function test_report_command_noops_when_unregistered(): void
    {
        Http::fake();
        $this->artisan('agent:report')->assertSuccessful();
        Http::assertNothingSent();
    }
}
