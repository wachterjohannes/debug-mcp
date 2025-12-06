# Creating MCP Extensions

MCP extensions are Composer packages that declare themselves using a specific package type, similar to PHPStan extensions.

## Quick Start

### 1. Configure composer.json

```json
{
  "name": "vendor/my-extension",
  "type": "library",
  "require": {
    "symfony/ai-mate": "^1.0"
  },
  "extra": {
    "ai-mate": {
      "scan-dirs": ["src", "lib"]
    }
  }
}
```

The `extra.ai-mate` section is required for your package to be discovered as an extension.

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

The `discover` command will automatically add your extension to `.mate/extensions.php`:

```php
return [
    'vendor/my-extension' => ['enabled' => true],
];
```

To disable an extension, set `enabled` to `false`:

```php
return [
    'vendor/my-extension' => ['enabled' => true],
    'vendor/unwanted-extension' => ['enabled' => false],
];
```

## Dependency Injection

Tools, Resources, and Prompts support constructor dependency injection via Symfony's DI Container. Dependencies are
automatically resolved and injected.

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

## Configuration Reference

**Scan Directories:** `extra.ai-mate.scan-dirs` (optional)
- Default: Package root directory
- Relative to package root
- Multiple directories supported

**Service Includes:** `extra.ai-mate.includes` (optional)
- Array of service configuration file paths
- Standard Symfony DI configuration format (PHP files)
- Supports environment variables via `%env()%`


**Security:** Extensions must be explicitly enabled in `.mate/extensions.php`
- The `discover` command automatically adds discovered extensions to `.mate/extensions.php`
- All extensions default to `enabled: true` when discovered
- Set `enabled: false` to disable an extension

## Troubleshooting

- **Not discovered?** Ensure `extra.ai-mate` section exists in composer.json
- **Not loaded?** Check that extension is enabled in `.mate/extensions.php`
- **Capabilities not found?** Verify MCP attributes and scan directories
- **Dependency not found?** Check services.php or ensure interface has implementation
