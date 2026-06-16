<?php
namespace App\Console\Commands;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use App\Agent\SalesAggregator;
use Illuminate\Console\Command;

class AgentReport extends Command
{
    protected $signature = 'agent:report';
    protected $description = 'Report yesterday\'s paid sales to the central control plane';

    public function handle(AgentConfig $config, SalesAggregator $aggregator, AgentClient $client): int
    {
        if (! $config->isRegistered()) {
            $this->info('Agent not registered; skipping.');
            return self::SUCCESS;
        }

        $start = now()->subDay()->startOfDay();
        $end = now()->subDay()->endOfDay();
        $totals = $aggregator->forPeriod($start, $end);

        $currency = $config->get('currency', env('DEFAULT_CURRENCY_CODE', 'USD'));
        $accepted = $client->report($start, $end, $totals['gross_sales'], $totals['order_count'], $currency);

        $this->info($accepted ? 'Report accepted.' : 'Report not accepted (held/fail-open).');
        return self::SUCCESS;
    }
}
