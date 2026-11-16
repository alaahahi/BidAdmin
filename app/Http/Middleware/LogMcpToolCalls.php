<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogMcpToolCalls
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $payload = $request->json()?->all() ?? [];
        if (($payload['method'] ?? null) !== 'tools/call') {
            return $response;
        }

        $user = $request->user();
        if (! $user) {
            return $response;
        }

        $tool = $payload['params']['name'] ?? null;
        $arguments = $payload['params']['arguments'] ?? [];

        ActivityLog::create([
            'type' => 'mcp_tool',
            'source' => 'mcp',
            'user_id' => $user->id,
            'customer_id' => $user->customer_id,
            'target' => is_string($tool) ? $tool : null,
            'description' => 'MCP tool call'.($tool ? ": {$tool}" : ''),
            'app' => 'mcp',
            'ip_address' => $request->ip(),
            'meta' => [
                'tool' => $tool,
                'arguments' => is_array($arguments) ? $this->redact($arguments) : [],
            ],
        ]);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function redact(array $arguments): array
    {
        foreach (['password', 'token', 'secret'] as $key) {
            if (array_key_exists($key, $arguments)) {
                $arguments[$key] = '[redacted]';
            }
        }

        return $arguments;
    }
}
