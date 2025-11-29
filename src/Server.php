<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp;

use Mcp\Server as McpServer;
use Mcp\Server\Transport\StdioTransport;
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
        // Build MCP server
        $builder = McpServer::builder()
            ->setServerInfo('debug-mcp', '0.1.0', 'Extensible MCP server for PHP development');

        // Add discovery loader to scan for tools/resources/prompts
        // This will scan vendor packages and local mcp/ directory
        $basePath = dirname(__DIR__);
        $builder->addLoaders(
            new \Mcp\Capability\Registry\Loader\DiscoveryLoader(
                basePath: $basePath,
                scanDirs: ['mcp', 'vendor'],
                excludeDirs: ['tests', 'var', 'public'],
                logger: new \Psr\Log\NullLogger(),
            )
        );

        // Build the server
        $this->mcpServer = $builder->build();

        // Create stdio transport and run server
        $transport = new StdioTransport();

        // Start listening (blocks until stdin EOF)
        $this->mcpServer->run($transport);
    }
}
