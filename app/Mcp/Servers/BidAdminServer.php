<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ActivityLogTool;
use App\Mcp\Tools\AssignAccountTool;
use App\Mcp\Tools\CreateInvoiceTool;
use App\Mcp\Tools\DashboardStatsTool;
use App\Mcp\Tools\GetAccountTool;
use App\Mcp\Tools\ListAccountsTool;
use App\Mcp\Tools\ListBidsTool;
use App\Mcp\Tools\ListBrokersTool;
use App\Mcp\Tools\ListCustomersTool;
use App\Mcp\Tools\ListInvoicesTool;
use App\Mcp\Tools\LogActivityTool;
use App\Mcp\Tools\LogBidTool;
use App\Mcp\Tools\MarkInvoicePaidTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('BidAdmin')]
#[Version('1.0.0')]
#[Instructions('BidAdmin MCP server for a multi-tenant shipping/auction ERP. Use it to inspect and manage auction accounts (Copart, IAAI, Manheim, EdgePipeline, NPA), customers, brokers, bids, activity logs, invoices, and dashboard stats. Read tools are available to any authenticated tenant user (customers only see their own data). Write tools require an admin role. Never request or expect auction account passwords — they are never exposed.')]
class BidAdminServer extends Server
{
    protected array $tools = [
        ListAccountsTool::class,
        GetAccountTool::class,
        ListCustomersTool::class,
        ListBrokersTool::class,
        ListBidsTool::class,
        ActivityLogTool::class,
        ListInvoicesTool::class,
        DashboardStatsTool::class,
        LogBidTool::class,
        LogActivityTool::class,
        AssignAccountTool::class,
        CreateInvoiceTool::class,
        MarkInvoicePaidTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
