<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function mcpHeaders(?string $token = null): array
    {
        $headers = [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
        ];

        if ($token !== null) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function initializePayload(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => (object) [],
                'clientInfo' => [
                    'name' => 'bidadmin-test',
                    'version' => '1.0.0',
                ],
            ],
        ];
    }

    public function test_mcp_endpoint_requires_sanctum_token(): void
    {
        $this->withHeaders($this->mcpHeaders())
            ->postJson('/mcp', $this->initializePayload())
            ->assertUnauthorized();
    }

    public function test_authenticated_admin_can_reach_mcp_endpoint(): void
    {
        $admin = User::where('email', 'admin@bidadmin.test')->firstOrFail();
        $token = $admin->createToken('mcp-test')->plainTextToken;

        $response = $this->withHeaders($this->mcpHeaders($token))
            ->postJson('/mcp', $this->initializePayload());

        $this->assertNotEquals(401, $response->status(), $response->getContent());
        $this->assertTrue(
            in_array($response->status(), [200, 202], true) || $response->json('result') !== null || $response->json('jsonrpc') === '2.0',
            'Expected a valid MCP response, got '.$response->status().': '.$response->getContent()
        );
    }
}
