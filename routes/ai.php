<?php

use App\Http\Middleware\LogMcpToolCalls;
use App\Mcp\Servers\BidAdminServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('bidadmin', BidAdminServer::class);

Mcp::web('/{tenant}/mcp', BidAdminServer::class)
    ->middleware([
        'api',
        'tenant',
        'auth:sanctum',
        'throttle:mcp',
        LogMcpToolCalls::class,
    ]);
