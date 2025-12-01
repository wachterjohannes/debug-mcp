# Creating MCP Extensions

MCP extensions are Composer packages that declare themselves using a specific package type, similar to PHPStan extensions.

## Quick Start

### 1. Configure composer.json

```json
{
  "name": "vendor/my-extension",
  "type": "ai-mate-extension",
  "require": {
    "mcp/sdk": "^1.0"
  }
}
```

**Optional:** Specify custom scan directories (defaults to package root):

```json
{
  "type": "ai-mate-extension",
  "extra": {
    "ai-mate": {
      "scan-dirs": ["src", "lib"]
    }
  }
}
```

### 2. Create MCP Capabilities

```php
<?php

use Mcp\Capability\Attribute\McpTool;
use Psr\Log\LoggerInterface;

class MyTool
{
    // Dependencies are automatically injected
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[McpTool(name: 'my-tool', description: 'What this tool does')]
    public function execute(string $param): string
    {
        $this->logger->info('Tool executed', ['param' => $param]);
        return "Result: " . $param;
    }
}
```

### 3. Install and Enable

```bash
composer require vendor/my-extension
php bin/mate.php discover
```

Add to `.mcp.php`:

```php
return [
    'enabled_plugins' => [
        // Include all capabilities from package
        'vendor/my-extension',

        // Exclude specific classes
        'vendor/other-extension' => [
            'exclude' => 'Vendor\\Package\\ExperimentalTool',
        ],

        // Or exclude multiple classes
        'vendor/third-extension' => [
            'exclude' => ['Class1', 'Class2'],
        ],

        // Only include specific classes
        'vendor/focused-extension' => [
            'include_only' => 'Vendor\\Package\\SpecificTool',
        ],
    ],
];
```

## Dependency Injection

Tools, resources, and prompts support constructor dependency injection via Symfony's DI Container. Dependencies are automatically resolved and injected.

### Configuring Services

Register service configuration files in your composer.json:

```json
{
  "type": "ai-mate-extension",
  "extra": {
    "ai-mate": {
      "scan-dirs": ["src"],
      "includes": [
        "config/services.php"
      ]
    }
  }
}
```

Create service configuration files using Symfony DI format:

```php
<?php
// config/services.php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    // Register a service with parameters
    $services->set(MyApiClient::class)
        ->arg('$apiKey', '%env(MY_API_KEY)%')
        ->arg('$baseUrl', 'https://api.example.com');
};
```

### Environment Variables

Use `%env(VAR_NAME)%` syntax in service configuration to reference environment variables.

## Configuration Reference

**Package Type:** `ai-mate-extension` (required)

**Scan Directories:** `extra.ai-mate.scan-dirs` (optional)
- Default: Package root directory
- Relative to package root
- Multiple directories supported

**Service Includes:** `extra.ai-mate.includes` (optional)
- Array of service configuration file paths
- Standard Symfony DI configuration format (PHP files)
- Supports environment variables via `%env()%`

**Plugin Filters:** Control which capabilities to load
- No filter: Include all capabilities from package
- `exclude`: Exclude specific class names (string or array)
- `include_only`: Only load specific class names (string or array)
- Cannot use both `exclude` and `include_only` for same package

**Security:** Extensions must be whitelisted in `enabled_plugins`

## Troubleshooting

- **Not discovered?** Check `type: "ai-mate-extension"` in composer.json
- **Not loaded?** Add package to `enabled_plugins` in .mcp.php
- **Capabilities not found?** Verify MCP attributes and scan directories
- **Dependency not found?** Check services.php or ensure interface has implementation
