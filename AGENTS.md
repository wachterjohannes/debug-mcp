# AI Mate Component

MCP server implementation for Symfony applications. Enables AI assistants to interact with Symfony projects through tools, resources, and prompts.

## Architecture

### Core Classes

- **`App`**: Symfony Console application builder for MCP server
- **`Command\ServeCommand`**: Starts MCP server with STDIO transport
- **`Command\InitCommand`**: Initializes `.mcp.php` configuration file
- **`Command\DiscoverCommand`**: Discovers and lists available MCP extensions
- **`Discovery\DiscoveryManager`**: Coordinates extension discovery from multiple sources
- **`Discovery\ComposerDiscovery`**: Scans vendor packages for MCP extensions
- **`Discovery\LocalDiscovery`**: Scans local `mcp/` directory using reflection
- **`Model\Configuration`**: Configuration container with validation
- **`Service\Logger`**: PSR-3 compliant logger for MCP server

### Key Directories

- **`src/Command/`**: Console commands (serve, init, discover)
- **`src/Discovery/`**: Extension discovery system
- **`src/Model/`**: Configuration and data models
- **`src/Service/`**: Logger and supporting services
- **`bin/`**: Executable entry points (mate, mate.php)
- **`mcp/`**: Local MCP extensions directory

### AI Assistant Support

- JetBrains AI Assistant
- Claude Desktop
- Cursor (planned)
- GitHub Copilot (planned)

## Commands

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

Or use composer script:
```bash
composer cs-fix
```

### Installing Dependencies

```bash
composer install
```

## Development

Uses **PHPUnit** with Symfony testing standards. Tests follow PSR-4 in `tests/` directory.

Follows **PSR-12** coding style and **Symfony Coding Standards** via PHP-CS-Fixer. All methods require strict type declarations.

## Extension System

### Vendor Extensions

Declared in `composer.json`:
```json
{
  "extra": {
    "symfony/ai-mate": {
      "classes": [
        "Vendor\\Package\\SomeTool"
      ]
    }
  }
}
```

Must be whitelisted in `.mcp.php`:
```php
return [
    'enabled_plugins' => [
        'vendor/package-name',
    ],
];
```

### Local Extensions

PHP files in `mcp/` directory with MCP attributes:
```php
use Mcp\Capability\Attribute\McpTool;

class MyTool
{
    #[McpTool('tool-name', 'Description')]
    public function execute(): array
    {
        return ['result' => 'value'];
    }
}
```

Always enabled for development.
