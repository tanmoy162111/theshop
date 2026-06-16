<?php
namespace App\Http\Middleware;

use App\Agent\AgentConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AgentEnforcement
{
    public function __construct(private AgentConfig $config) {}

    /** @param string $scope 'admin' | 'storefront' */
    public function handle(Request $request, Closure $next, string $scope = 'storefront')
    {
        $status = $this->currentStatus();

        // Warning: never blocks; surfaces a banner to views.
        if ($status === 'warning') {
            View::share('agent_banner', $this->config->get('status_message'));
        }

        $blockAdmin = in_array($status, ['locked_admin', 'maintenance'], true);
        $blockStore = $status === 'maintenance';

        if ($scope === 'admin' && $blockAdmin) {
            return $this->blocked($this->config->get('status_message') ?? 'Admin temporarily locked.');
        }
        if ($scope === 'storefront' && $blockStore) {
            return $this->blocked($this->config->get('status_message') ?? 'Store temporarily unavailable.');
        }

        return $next($request);
    }

    /** Fail-open: any error (incl. missing agent_settings table) → 'active' (allow). */
    private function currentStatus(): string
    {
        try {
            return $this->config->get('status', 'active') ?: 'active';
        } catch (\Throwable $e) {
            return 'active';
        }
    }

    private function blocked(string $message)
    {
        return response($message, 503);
    }
}
