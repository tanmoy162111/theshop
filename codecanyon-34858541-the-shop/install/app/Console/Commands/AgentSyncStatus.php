<?php
namespace App\Console\Commands;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use Illuminate\Console\Command;

class AgentSyncStatus extends Command
{
    protected $signature = 'agent:sync-status';
    protected $description = 'Fetch latest status + commission config from the control plane';

    public function handle(AgentConfig $config, AgentClient $client): int
    {
        if (! $config->isRegistered()) {
            $this->info('Agent not registered; skipping.');
            return self::SUCCESS;
        }
        $client->syncStatus();
        $this->info('Status: ' . $config->get('status', 'unknown'));
        return self::SUCCESS;
    }
}
