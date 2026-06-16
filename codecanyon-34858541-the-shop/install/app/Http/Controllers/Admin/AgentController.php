<?php
namespace App\Http\Controllers\Admin;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function settings(AgentConfig $config)
    {
        return view('backend.agent.settings', [
            'status' => $config->get('status', 'unregistered'),
            'central_url' => $config->get('central_url'),
            'commission_type' => $config->get('commission_type'),
            'commission_rate' => $config->get('commission_rate'),
            'last_synced_at' => $config->get('last_synced_at'),
        ]);
    }

    public function register(Request $request, AgentClient $client, AgentConfig $config)
    {
        $data = $request->validate([
            'central_url' => 'required|url',
            'business_name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'domain' => 'required|string|max:255',
        ]);

        $config->set('central_url', rtrim($data['central_url'], '/'));
        $client->register($data['business_name'], $data['contact_email'], $data['domain']);

        return redirect()->back()->with('success', 'Registered with control plane. Awaiting approval.');
    }

    public function sync(AgentClient $client)
    {
        $client->syncStatus();
        return redirect()->back()->with('success', 'Status synced.');
    }
}
