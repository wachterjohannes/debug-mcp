# debug-mcp - Core MCP Server

## Project Overview

This repository contains the core extensible MCP (Model Context Protocol) server implementation in PHP. It serves as the central component of the debug-mcp ecosystem, providing a plugin architecture for tools, resources, and prompts.

**Role in Ecosystem**: Foundation server that discovers and orchestrates extensions from multiple sources

**Key Responsibility**: MCP protocol handling, extension discovery, and lifecycle management

## Architecture

### Component Structure

```
Server (Main Orchestrator)
├── DiscoveryManager
│   ├── ComposerDiscovery
│   ├── Extra Config Reader
│   └── LocalDiscovery
├── MCP SDK Integration
│   └── StdioTransport
└── Extension Instantiation
```

### Discovery System

The server implements a three-phase discovery mechanism:

1. **Composer Package Discovery** (`ComposerDiscovery`)
   - Reads `vendor/composer/installed.json`
   - Filters packages containing `extra.wachterjohannes/debug-mcp.classes`
   - Returns fully-qualified class names from installed packages

2. **Extra Config Discovery**
   - Reads root `composer.json`
   - Extracts classes from `extra.wachterjohannes/debug-mcp.classes`
   - Enables quick local registration without file scanning

3. **Local Directory Discovery** (`LocalDiscovery`)
   - Scans `mcp/` directory for PHP files
   - Uses reflection to find classes with MCP attributes
   - Enables rapid prototyping without composer packages

### MCP SDK Integration

Uses official `modelcontextprotocol/php-sdk`:

- **StdioTransport**: JSON-RPC 2.0 over stdin/stdout
- **Attribute Discovery**: SDK's built-in `#[McpTool]`, `#[McpResource]`, `#[McpPrompt]` attributes
- **Server Lifecycle**: Initialize, discover, register, listen loop
- **Protocol Compliance**: Automatic JSON-RPC message handling

## Development Guidelines

### Code Style

- **PSR-12**: Follow PSR-12 coding standards
- **Type Hints**: All parameters and returns must have type declarations
- **Attributes**: Use PHP 8 attributes for declarative configuration
- **Minimal Dependencies**: Only add dependencies when absolutely necessary

### Namespace Convention

```
Wachterjohannes\DebugMcp\
├── Server.php
├── Discovery\
│   ├── DiscoveryManager.php
│   ├── ComposerDiscovery.php
│   └── LocalDiscovery.php
└── Contract\
    └── DiscoverableInterface.php
```

### Extension Points

To add new discovery sources:

1. Create class in `src/Discovery/`
2. Implement discovery logic returning class names array
3. Update `DiscoveryManager::discover()` to include new source
4. Document in README.md

### Key Implementation Notes

**bin/debug-mcp Entry Point**:
```php
#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

$server = new \Wachterjohannes\DebugMcp\Server();
$server->run();
```

**Server::run() Flow**:
1. Initialize MCP server from SDK
2. Run DiscoveryManager to get all extension classes
3. Instantiate extension classes (they auto-register via attributes)
4. SDK discovers capabilities via reflection
5. Start listening on stdin with StdioTransport
6. Process JSON-RPC messages until EOF

**Discovery Pseudocode**:
```php
public function discover(): array
{
    $classes = [];

    // Phase 1: Vendor packages
    $classes = array_merge($classes, $this->composerDiscovery->scan());

    // Phase 2: Extra config
    $classes = array_merge($classes, $this->readExtraConfig());

    // Phase 3: Local directory
    $classes = array_merge($classes, $this->localDiscovery->scan());

    return array_unique($classes);
}
```

## Integration Points

### With Extension Packages

Extension packages declare discoverable classes in their composer.json:

```json
{
  "extra": {
    "wachterjohannes/debug-mcp": {
      "classes": [
        "Vendor\\Package\\SomeTool",
        "Vendor\\Package\\SomeResource"
      ]
    }
  }
}
```

After `composer install`, the server automatically discovers and loads these classes.

### With Go Wrapper

The Go wrapper (`debug-mcp-go-wrapper`) manages this PHP process:

- Starts via `php bin/debug-mcp`
- Proxies stdin/stdout for MCP communication
- Restarts every 60 seconds for memory management
- Buffers messages during restart window

The PHP server is stateless and should:
- Not maintain in-memory state across requests
- Handle each JSON-RPC message independently
- Exit cleanly on stdin EOF

### With Claude Desktop

Claude Desktop connects to the wrapper (not directly to PHP):

```json
{
  "mcpServers": {
    "debug-mcp": {
      "command": "/path/to/debug-mcp-wrapper",
      "args": ["--cwd", "/path/to/debug-mcp"]
    }
  }
}
```

## Testing Approach

### Manual Testing

1. **Direct Execution Test**:
   ```bash
   php bin/debug-mcp
   ```
   Should start and wait for JSON-RPC input on stdin.

2. **Discovery Verification**:
   Install extension packages and verify they're discovered on startup.
   Check logs or add debug output to DiscoveryManager.

3. **Protocol Compliance**:
   Send JSON-RPC messages via stdin and verify responses on stdout:
   ```json
   {"jsonrpc":"2.0","method":"tools/list","id":1}
   ```

### Integration Testing

Test with Claude Desktop or MCP client:
- Install all extension packages
- Configure Claude Desktop
- Verify tools, resources, and prompts are available
- Test actual tool execution

## SDK Usage Notes

### Official modelcontextprotocol/php-sdk

This SDK is experimental (pre-v1.0) but official from Anthropic/Symfony:

- **Installation**: `composer require modelcontextprotocol/php-sdk`
- **Stability**: API may change before v1.0
- **Documentation**: Limited, refer to source code and examples
- **Attributes**: `#[McpTool]`, `#[McpResource]`, `#[McpPrompt]` from SDK

### Key SDK Classes

- `PhpMcp\Server\Server`: Main server class
- `PhpMcp\Server\Transports\StdioServerTransport`: Stdio transport
- `PhpMcp\Server\Attributes\McpTool`: Tool attribute
- `PhpMcp\Server\Attributes\McpResource`: Resource attribute
- `PhpMcp\Server\Attributes\McpPrompt`: Prompt attribute

### Attribute-Based Discovery

The SDK uses PHP reflection to find attributes:

```php
use Mcp\Capability\Attribute\McpTool;

class MyTool
{
    #[McpTool(name: 'my_tool', description: 'My tool')]
    public function execute(string $param): array
    {
        return ['result' => $param];
    }
}
```

Just instantiate the class - the SDK discovers the method via reflection.

## Future Extensions

### Potential Enhancements

1. **Discovery Caching**: Cache discovered classes to improve startup time
2. **Configuration File**: Support `debug-mcp.json` for server configuration
3. **Hot Reload**: Watch mcp/ directory and reload on file changes
4. **Performance Metrics**: Log discovery time and extension count
5. **Validation**: Validate extension classes before instantiation
6. **Plugin API**: Formal interface for plugins to register hooks

### Not Implementing (Prototype Phase)

- Unit tests (focus on discussion, not production)
- CI/CD (manual execution only)
- Comprehensive error handling (basic errors only)
- Performance optimization (functionality over speed)
- Production hardening (dev/prototype environment)

## Key Design Decisions

1. **Three-Source Discovery**: Maximum flexibility for different use cases
2. **Official SDK**: Use Anthropic's official PHP implementation despite experimental status
3. **Attribute-Based**: Leverages modern PHP 8 features for clean code
4. **Minimal Dependencies**: Only MCP SDK required, everything else optional
5. **Stateless Design**: Each request handled independently for Go wrapper compatibility

## Quick Implementation Checklist

- [ ] `bin/debug-mcp` - Entry point executable
- [ ] `src/Server.php` - Main orchestrator
- [ ] `src/Discovery/DiscoveryManager.php` - Discovery coordinator
- [ ] `src/Discovery/ComposerDiscovery.php` - Vendor package scanner
- [ ] `src/Discovery/LocalDiscovery.php` - Local directory scanner
- [ ] `composer.json` - Package definition
- [ ] `README.md` - User documentation
- [ ] `.php-cs-fixer.php` - Code style configuration
- [ ] Basic integration test with extension package
