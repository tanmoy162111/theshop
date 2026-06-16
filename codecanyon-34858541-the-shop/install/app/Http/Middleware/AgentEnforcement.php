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
        [$status, $message] = $this->currentState();

        // Warning: never blocks; surfaces a banner to views.
        if ($status === 'warning') {
            View::share('agent_banner', $message);
        }

        // maintenance blocks both admin and storefront; locked_admin blocks only admin.
        $adminBlocked = in_array($status, ['locked_admin', 'maintenance'], true);
        $storeDown = $status === 'maintenance';

        if ($scope === 'admin' && $adminBlocked) {
            return $this->blocked($message ?? 'Admin temporarily locked.');
        }
        if ($scope === 'storefront' && $storeDown) {
            return $this->blocked($message ?? 'Store temporarily unavailable.');
        }

        return $next($request);
    }

    /**
     * Fail-open: any error (incl. missing agent_settings table or a transient
     * DB failure on either read) yields ['active', null] so the store stays up.
     *
     * @return array{0: string, 1: ?string}
     */
    private function currentState(): array
    {
        try {
            $status = $this->config->get('status', 'active') ?: 'active';
            $message = $this->config->get('status_message');
            return [$status, $message];
        } catch (\Throwable) {
            return ['active', null];
        }
    }

    private function blocked(string $message)
    {
        return response($message, 503);
    }
}
