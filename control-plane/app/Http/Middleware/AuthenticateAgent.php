<?php
namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Missing token'], 401);
        }

        $client = Client::where('token', hash('sha256', $token))->first();
        if (! $client) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if ($request->header('X-Agent-Domain') !== $client->primary_domain) {
            return response()->json(['message' => 'Domain mismatch'], 403);
        }

        $client->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('agent_client', $client);

        return $next($request);
    }
}
