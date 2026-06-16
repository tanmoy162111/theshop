<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\CommissionCalculator;
use Illuminate\Http\Request;

class ClientAdminController extends Controller
{
    public function index(CommissionCalculator $calc)
    {
        $clients = Client::withSum('reports as gross_total', 'gross_sales')
            ->withSum('reports as orders_total', 'order_count')
            ->orderByDesc('created_at')->get()
            ->map(function (Client $c) use ($calc) {
                $c->commission_owed = $calc->owed(
                    $c->commission_type, (float) $c->commission_rate,
                    (float) ($c->gross_total ?? 0), (int) ($c->orders_total ?? 0)
                );
                return $c;
            });

        return view('clients.index', compact('clients'));
    }

    public function show(Client $client, CommissionCalculator $calc)
    {
        $client->loadMissing('reports');
        $grossTotal = (float) $client->reports->sum('gross_sales');
        $ordersTotal = (int) $client->reports->sum('order_count');
        $commissionOwed = $calc->owed($client->commission_type, (float) $client->commission_rate, $grossTotal, $ordersTotal);

        return view('clients.show', compact('client', 'grossTotal', 'ordersTotal', 'commissionOwed'));
    }

    public function update(Request $request, Client $client)
    {
        $action = $request->input('action');

        if ($action === 'approve') {
            $client->forceFill(['status' => 'active', 'approved_at' => now()])->save();
            return back()->with('status', 'Client approved.');
        }

        if ($action === 'reject') {
            $client->forceFill(['status' => 'rejected'])->save();
            return back()->with('status', 'Client rejected.');
        }

        $data = $request->validate([
            'commission_type' => 'required|in:percent,per_order',
            'commission_rate' => 'required|numeric|min:0',
            'status'          => 'required|in:active,warning,locked_admin,maintenance',
        ]);
        $client->update($data);

        return back()->with('status', 'Client updated.');
    }
}
