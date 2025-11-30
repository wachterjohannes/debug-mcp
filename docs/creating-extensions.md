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

class MyTool
{
    #[McpTool(name: 'my-tool', description: 'What this tool does')]
    public function execute(string $param): string
    {
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
        'vendor/my-extension',
    ],
];
```

## Configuration Reference

**Package Type:** `ai-mate-extension` (required)

**Scan Directories:** `extra.ai-mate.scan-dirs` (optional)
- Default: Package root directory
- Relative to package root
- Multiple directories supported

**Security:** Extensions must be whitelisted in `enabled_plugins`

## Troubleshooting

- **Not discovered?** Check `type: "ai-mate-extension"` in composer.json
- **Not loaded?** Add package to `enabled_plugins` in .mcp.php
- **Capabilities not found?** Verify MCP attributes and scan directories
