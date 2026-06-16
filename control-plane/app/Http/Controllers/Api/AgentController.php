<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'domain'        => 'required|string|max:255',
            'app_version'   => 'nullable|string|max:50',
        ]);

        $existing = Client::where('primary_domain', $data['domain'])->first();
        if ($existing) {
            return response()->json(['status' => $existing->status], 200);
        }

        $plain = Str::random(48);
        $client = Client::create([
            'business_name'  => $data['business_name'],
            'contact_email'  => $data['contact_email'],
            'primary_domain' => $data['domain'],
            'app_version'    => $data['app_version'] ?? null,
            'token'          => hash('sha256', $plain),
            'status'         => 'pending',
            'registered_at'  => now(),
        ]);

        return response()->json([
            'client_id' => $client->id,
            'token'     => $plain,
            'status'    => $client->status,
        ], 201);
    }

    public function report(Request $request)
    {
        /** @var Client $client */
        $client = $request->attributes->get('agent_client');

        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date',
            'gross_sales'  => 'required|numeric|min:0',
            'order_count'  => 'required|integer|min:0',
            'currency'     => 'required|string|size:3',
            'app_version'  => 'nullable|string|max:50',
        ]);

        if (in_array($client->status, ['pending', 'rejected'], true)) {
            return response()->json(['accepted' => false] + $this->statusPayload($client), 200);
        }

        $periodStart = Carbon::parse($data['period_start'])->startOfDay();
        $periodEnd = Carbon::parse($data['period_end'])->startOfDay();

        $client->reports()->updateOrCreate(
            ['period_start' => $periodStart, 'period_end' => $periodEnd],
            [
                'gross_sales' => $data['gross_sales'],
                'order_count' => $data['order_count'],
                'currency'    => $data['currency'],
                'received_at' => now(),
            ]
        );

        $client->forceFill([
            'last_report_at' => now(),
            'app_version'    => $data['app_version'] ?? $client->app_version,
        ])->save();

        return response()->json(['accepted' => true] + $this->statusPayload($client), 200);
    }

    public function status(Request $request)
    {
        return response()->json($this->statusPayload($request->attributes->get('agent_client')), 200);
    }

    private function statusPayload(Client $client): array
    {
        return [
            'status'          => $client->status,
            'commission_type' => $client->commission_type,
            'commission_rate' => $client->commission_rate,
            'message'         => $this->messageFor($client->status),
            'grace_until'     => null, // automatic grace handled in billing sub-project
        ];
    }

    private function messageFor(string $status): ?string
    {
        return match ($status) {
            'warning'      => 'Commission payment is overdue. Please settle to avoid interruption.',
            'locked_admin' => 'Admin access is locked pending commission payment.',
            'maintenance'  => 'This store is temporarily unavailable.',
            default        => null,
        };
    }
}
