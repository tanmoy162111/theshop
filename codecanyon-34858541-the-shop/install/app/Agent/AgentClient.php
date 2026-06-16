<?php
namespace App\Agent;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentClient
{
    public function __construct(private AgentConfig $config) {}

    private function base(): string
    {
        return rtrim((string) $this->config->get('central_url'), '/');
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('token'),
            'X-Agent-Domain' => $this->domain(),
            'Accept' => 'application/json',
        ];
    }

    private function domain(): string
    {
        return (string) ($this->config->get('domain') ?: parse_url(config('app.url'), PHP_URL_HOST));
    }

    public function register(string $businessName, string $email, string $domain): string
    {
        $this->config->set('domain', $domain);

        $res = Http::acceptJson()->post($this->base() . '/api/v1/agent/register', [
            'business_name' => $businessName,
            'contact_email' => $email,
            'domain' => $domain,
            'app_version' => config('app.version', '1.0.0'),
        ])->throw()->json();

        if (! empty($res['token'])) {
            $this->config->set('token', $res['token']);
            $this->config->set('client_id', (string) $res['client_id']);
        }
        $this->config->set('status', $res['status']);

        return $res['status'];
    }

    public function report(CarbonInterface $start, CarbonInterface $end, float $gross, int $orders, string $currency): bool
    {
        try {
            $res = Http::withHeaders($this->authHeaders())
                ->post($this->base() . '/api/v1/agent/report', [
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'gross_sales' => $gross,
                    'order_count' => $orders,
                    'currency' => $currency,
                    'app_version' => config('app.version', '1.0.0'),
                ])->throw()->json();

            $this->applyStatusPayload($res);
            return (bool) ($res['accepted'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('Agent report failed (fail-open): ' . $e->getMessage());
            return false;
        }
    }

    public function syncStatus(): void
    {
        try {
            $res = Http::withHeaders($this->authHeaders())
                ->get($this->base() . '/api/v1/agent/status')->throw()->json();
            $this->applyStatusPayload($res);
        } catch (\Throwable $e) {
            Log::warning('Agent status sync failed (fail-open): ' . $e->getMessage());
            // keep last known status
        }
    }

    private function applyStatusPayload(array $res): void
    {
        foreach ([
            'status' => 'status',
            'commission_type' => 'commission_type',
            'commission_rate' => 'commission_rate',
            'message' => 'status_message',
        ] as $from => $to) {
            if (array_key_exists($from, $res)) {
                $this->config->set($to, $res[$from] === null ? null : (string) $res[$from]);
            }
        }
        $this->config->set('last_synced_at', now()->toIso8601String());
    }
}
