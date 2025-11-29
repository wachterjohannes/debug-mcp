# debug-mcp

**⚠️ PROTOTYPE - FOR TESTING AND DISCUSSION PURPOSES ONLY**

---

Extensible MCP (Model Context Protocol) server for PHP development with multi-source discovery mechanism.

## Purpose

This is the core MCP server that provides an extensible architecture for adding tools, resources, and prompts through:
- Composer packages with auto-discovery
- Local extensions in the `mcp/` directory
- Extra configuration in composer.json

## Features

- **Multi-Source Discovery**: Automatically discovers extensions from vendor packages, extra config, and local directories
- **Attribute-Based Registration**: Uses PHP 8 attributes for clean, declarative tool/resource/prompt definitions
- **Composer Integration**: Standard package installation with `composer require`
- **Local Development**: Rapid prototyping with the `mcp/` directory
- **MCP Protocol Compliant**: Implements JSON-RPC 2.0 over stdio using official `modelcontextprotocol/php-sdk`

## Installation

```bash
composer create-project wachterjohannes/debug-mcp
cd debug-mcp
composer install
```

## Usage

### Direct Execution

```bash
php bin/debug-mcp
```

The server will start and listen for MCP protocol messages on stdin, responding on stdout.

### With Go Wrapper (Recommended)

Use the [debug-mcp-go-wrapper](../debug-mcp-go-wrapper) for automatic process management with memory isolation and periodic restarts:

```bash
/path/to/debug-mcp-wrapper --cwd=/path/to/debug-mcp
```

### Claude Desktop Configuration

```json
{
  "mcpServers": {
    "debug-mcp": {
      "command": "/path/to/debug-mcp-wrapper",
      "args": ["--cwd", "/absolute/path/to/debug-mcp"]
    }
  }
}
```

## Extension Development

### Method 1: Composer Package

Create a new package with your tools/resources/prompts:

```json
{
  "name": "your-vendor/your-package",
  "require": {
    "modelcontextprotocol/php-sdk": "^0.1"
  },
  "autoload": {
    "psr-4": {
      "YourVendor\\YourPackage\\": "src/"
    }
  },
  "extra": {
    "wachterjohannes/debug-mcp": {
      "classes": [
        "YourVendor\\YourPackage\\YourTool",
        "YourVendor\\YourPackage\\YourResource"
      ]
    }
  }
}
```

Install with:
```bash
composer require your-vendor/your-package
```

### Method 2: Local mcp/ Directory

For rapid prototyping, add PHP files directly to the `mcp/` directory:

```php
<?php
namespace Local;

use PhpMcp\Server\Attributes\McpTool;

class MyTool
{
    #[McpTool(
        name: 'my_tool',
        description: 'Description of my tool'
    )]
    public function execute(string $param): array
    {
        return ['result' => 'value'];
    }
}
```

The server will automatically discover and register it on startup.

## Architecture

```
debug-mcp Server
├── Discovery System
│   ├── ComposerDiscovery (scans vendor packages)
│   ├── Extra Config Reader (reads composer.json extra field)
│   └── LocalDiscovery (scans mcp/ directory)
├── MCP SDK Integration
│   └── StdioTransport (JSON-RPC over stdin/stdout)
└── Extension Registry
    ├── Tools
    ├── Resources
    └── Prompts
```

## Available Extensions

Install these optional packages to extend functionality:

- **[debug-mcp-tools](../debug-mcp-tools)**: Debugging tools (clock, PHP config)
- **[debug-mcp-resources](../debug-mcp-resources)**: PHP and Symfony documentation
- **[debug-mcp-prompts](../debug-mcp-prompts)**: Code generation prompts

## Development

### Code Quality

Format code before committing:

```bash
composer cs-fix
```

### Adding New Discovery Sources

Implement the discovery logic in `src/Discovery/` and update `DiscoveryManager` to include your new source.

## Requirements

- PHP 8.1 or higher
- Composer
- modelcontextprotocol/php-sdk

## License

MIT
