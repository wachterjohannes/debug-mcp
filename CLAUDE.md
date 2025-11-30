# AI Mate Component

This component provides MCP (Model Context Protocol) server implementation for Symfony applications. It enables AI assistants to interact with Symfony projects through tools, resources, and prompts.

## Development Commands

### Testing

```bash
vendor/bin/phpunit
```

### Code Quality

Run static analysis:
```bash
vendor/bin/phpstan
```

Format code:
```bash
vendor/bin/php-cs-fixer fix
```

Or using composer scripts:
```bash
composer cs-fix
```

### Installing Dependencies

```bash
composer install
```

## Architecture

### Core Classes

- **`App`**: Symfony Console application builder
- **`Command\ServeCommand`**: Starts the MCP server with STDIO transport
- **`Command\InitCommand`**: Initializes `.mcp.php` configuration
- **`Command\DiscoverCommand`**: Discovers available MCP extensions
- **`Discovery\DiscoveryManager`**: Coordinates extension discovery
- **`Discovery\ComposerDiscovery`**: Scans vendor packages for MCP extensions
- **`Discovery\LocalDiscovery`**: Scans local `mcp/` directory
- **`Model\Configuration`**: Configuration model
- **`Service\Logger`**: PSR-3 logger implementation

### Directories

- **`src/Command/`**: Console commands for server lifecycle
- **`src/Discovery/`**: Extension discovery system
- **`src/Model/`**: Configuration and data models
- **`src/Service/`**: Supporting services
- **`bin/`**: Executable entry points
- **`mcp/`**: Local MCP extensions directory

### Extension Support

The component supports MCP capabilities:
- **Tools**: Executable functions exposed to AI assistants
- **Resources**: Data sources and templates
- **Prompts**: Pre-configured prompts for AI assistants

Extensions can be:
- **Vendor packages**: Declared in `composer.json` extra section
- **Local files**: PHP files in `mcp/` directory with MCP attributes

## Testing Architecture

This component uses **PHPUnit** with standard Symfony testing practices.

### Test Structure

Tests follow PSR-4 autoloading in `tests/` directory (to be added).

### Code Standards

This component follows:
- **PSR-12**: Coding style
- **Symfony Coding Standards**: PHP-CS-Fixer configuration
- **Type Safety**: Strict type declarations on all methods

## Security Model

**Whitelist-only plugins**: Vendor packages must be explicitly enabled in `.mcp.php` configuration. Local `mcp/` directory is always enabled for development.

## Integration Points

- **JetBrains AI Assistant**: STDIO transport via `php vendor/bin/mate serve`
- **Claude Desktop**: JSON configuration with command and args
- **MCP SDK**: Official `mcp/sdk` package for protocol implementation
