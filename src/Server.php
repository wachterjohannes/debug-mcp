<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp;

use PhpMcp\Server\Server as McpServer;
use PhpMcp\Server\Transports\StdioServerTransport;
use Wachterjohannes\DebugMcp\Discovery\DiscoveryManager;

/**
 * Main MCP server orchestrator.
 *
 * Coordinates discovery and initialization of MCP extensions from multiple sources,
 * then starts the MCP server with stdio transport for JSON-RPC communication.
 */
class Server
{
    private DiscoveryManager $discoveryManager;
    private McpServer $mcpServer;

    public function __construct(?DiscoveryManager $discoveryManager = null)
    {
        $this->discoveryManager = $discoveryManager ?? new DiscoveryManager();
    }

    /**
     * Run the MCP server.
     *
     * Flow:
     * 1. Initialize MCP server from SDK
     * 2. Run DiscoveryManager to get all extension classes
     * 3. Instantiate extension classes (auto-register via attributes)
     * 4. SDK discovers capabilities via reflection
     * 5. Start listening on stdin with StdioTransport
     * 6. Process JSON-RPC messages until EOF
     */
    public function run(): void
    {
        // Initialize MCP server
        $this->mcpServer = new McpServer(
            name: 'debug-mcp',
            version: '0.1.0'
        );

        // Discover all extension classes
        $extensionClasses = $this->discoveryManager->discover();

        // Instantiate extension classes (they auto-register via attributes)
        foreach ($extensionClasses as $className) {
            if (! class_exists($className)) {
                error_log("Warning: Class {$className} not found, skipping.");

                continue;
            }

            try {
                $instance = new $className();
                $this->mcpServer->registerInstance($instance);
            } catch (\Throwable $e) {
                error_log("Error instantiating {$className}: " . $e->getMessage());
            }
        }

        // Create stdio transport for JSON-RPC communication
        $transport = new StdioServerTransport();

        // Start listening (blocks until stdin EOF)
        $this->mcpServer->listen($transport);
    }
}
