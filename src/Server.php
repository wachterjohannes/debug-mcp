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

    public function __construct(?DiscoveryManager $discoveryManager = null, ?string $rootDir = null)
    {
        $rootDir = $rootDir ?? getcwd();
        $this->discoveryManager = $discoveryManager ?? new DiscoveryManager(rootDir: $rootDir);
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
        $basePath = getcwd();

        // Build MCP server
        $builder = McpServer::builder()
            ->setServerInfo('debug-mcp', '0.1.0', 'Extensible MCP server for PHP development')
            ->setDiscovery(
                basePath: $basePath,
                scanDirs: ['mcp'],
            );

        // Discover and manually register tool/resource/prompt classes from composer packages
        $discoveredClasses = $this->discoveryManager->discover();
        foreach ($discoveredClasses as $class) {
            // Try to autoload the class by attempting to use it
            if (! class_exists($class, true)) {
                // Class doesn't exist even after autoload attempt
                continue;
            }

            try {
                // Use reflection to find which type of MCP element this is
                $reflection = new \ReflectionClass($class);
                foreach ($reflection->getMethods() as $method) {
                    $attributes = $method->getAttributes();
                    foreach ($attributes as $attribute) {
                        $attrName = $attribute->getName();

                        // Instantiate the attribute to get its properties
                        $attrInstance = $attribute->newInstance();

                        // Add to builder based on attribute type with name/description from attribute
                        // Check ResourceTemplate BEFORE Resource since ResourceTemplate contains "Resource"
                        if (str_contains($attrName, 'McpTool')) {
                            $builder->addTool(
                                handler: [$class, $method->getName()],
                                name: $attrInstance->name ?? $method->getName(),
                                description: $attrInstance->description ?? null
                            );
                        } elseif (str_contains($attrName, 'McpResourceTemplate')) {
                            // Resource template with URI pattern
                            $uriTemplate = $attrInstance->uriTemplate ?? null;
                            if (empty($uriTemplate)) {
                                continue;
                            }
                            $builder->addResourceTemplate(
                                handler: [$class, $method->getName()],
                                uriTemplate: $uriTemplate,
                                name: $attrInstance->name ?? $method->getName(),
                                description: $attrInstance->description ?? null,
                                mimeType: $attrInstance->mimeType ?? null
                            );
                        } elseif (str_contains($attrName, 'McpResource')) {
                            // Regular resource with fixed URI
                            $uri = $attrInstance->uri ?? null;
                            if (empty($uri)) {
                                continue;
                            }
                            $builder->addResource(
                                handler: [$class, $method->getName()],
                                uri: $uri,
                                name: $attrInstance->name ?? $method->getName(),
                                description: $attrInstance->description ?? null
                            );
                        } elseif (str_contains($attrName, 'McpPrompt')) {
                            $builder->addPrompt(
                                handler: [$class, $method->getName()],
                                name: $attrInstance->name ?? $method->getName(),
                                description: $attrInstance->description ?? null
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Skip classes with invalid or missing attribute classes
                // This allows the server to start even if some packages have wrong SDK versions
                error_log("DEBUG: Skipped class $class: " . $e->getMessage());
                continue;
            }
        }

        $this->mcpServer = $builder->build();

        // Create stdio transport and run server
        $transport = new StdioTransport();

        // Start listening (blocks until stdin EOF)
        $this->mcpServer->run($transport);
    }
}
