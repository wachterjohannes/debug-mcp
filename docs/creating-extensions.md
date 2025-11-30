# Creating MCP Extensions

This guide explains how to create MCP extensions for Symfony AI Mate using the Composer package type system.

## Extension Package Structure

MCP extensions are Composer packages that declare themselves using a specific package type. This is similar to how PHPStan extensions work.

### Required composer.json Configuration

Your extension package must declare the `ai-mate-extension` type:

```json
{
  "name": "vendor/my-mcp-extension",
  "type": "ai-mate-extension",
  "description": "My custom MCP tools and features",
  "require": {
    "mcp/sdk": "^1.0"
  },
  "extra": {
    "ai-mate": {
      "scan-dirs": [
        "src/Mcp"
      ]
    }
  }
}
```

### Configuration Details

**Required Fields:**
- `type`: Must be `"ai-mate-extension"`
- `name`: Your package name (e.g., `vendor/package-name`)

**Optional Extra Configuration:**
- `extra.ai-mate.scan-dirs`: Array of directories to scan for MCP capabilities
  - Default: `["src/Mcp"]` if not specified
  - Paths are relative to your package root
  - Multiple directories are supported

### Directory Structure

Recommended structure for your extension package:

```
vendor/my-mcp-extension/
├── composer.json
└── src/
    └── Mcp/
        ├── Tools/
        │   ├── MyTool.php
        │   └── AnotherTool.php
        ├── Resources/
        │   └── MyResource.php
        └── Prompts/
            └── MyPrompt.php
```

### Creating MCP Capabilities

Use the MCP SDK attributes to define your capabilities:

#### Tools

```php
<?php

namespace Vendor\MyExtension\Mcp\Tools;

use Mcp\Capability\Attribute\McpTool;

class MyTool
{
    #[McpTool(
        name: 'my-tool',
        description: 'Description of what this tool does'
    )]
    public function execute(string $param): string
    {
        return "Result: " . $param;
    }
}
```

#### Resources

```php
<?php

namespace Vendor\MyExtension\Mcp\Resources;

use Mcp\Capability\Attribute\McpResource;

class MyResource
{
    #[McpResource(
        name: 'my-resource',
        description: 'Description of this resource'
    )]
    public function getData(): array
    {
        return ['key' => 'value'];
    }
}
```

#### Prompts

```php
<?php

namespace Vendor\MyExtension\Mcp\Prompts;

use Mcp\Capability\Attribute\McpPrompt;

class MyPrompt
{
    #[McpPrompt(
        name: 'my-prompt',
        description: 'Description of this prompt'
    )]
    public function getPrompt(): string
    {
        return "This is a pre-configured prompt template";
    }
}
```

## Installing Your Extension

### 1. Install via Composer

Install your extension package in the target project:

```bash
composer require vendor/my-mcp-extension
```

### 2. Discover Available Extensions

Run the discover command to see all available MCP extensions:

```bash
php bin/mate.php discover
```

This will show:
- All installed packages with type `symfony-ai-mate-extension`
- Their configured scan directories
- Suggested configuration for `.mcp.php`

### 3. Enable in Configuration

Add your extension to the whitelist in `.mcp.php`:

```php
<?php
// .mcp.php

return [
    'enabled_plugins' => [
        'vendor/my-mcp-extension',
    ],
];
```

**Security Note:** Extensions are opt-in only. Packages must be explicitly whitelisted in `enabled_plugins` to be loaded by the MCP server.

### 4. Verify Extension is Loaded

Start the MCP server and verify your extension's capabilities are available:

```bash
php bin/mate.php serve
```

## Multiple Scan Directories

If your extension has MCP capabilities in multiple directories, configure them in `extra.ai-mate.scan-dirs`:

```json
{
  "type": "ai-mate-extension",
  "extra": {
    "ai-mate": {
      "scan-dirs": [
        "src/Mcp/Tools",
        "src/Mcp/Resources",
        "lib/CustomMcp"
      ]
    }
  }
}
```

## Best Practices

1. **Namespace Isolation**: Use vendor-specific namespaces to avoid conflicts
2. **Clear Naming**: Use descriptive names for tools, resources, and prompts
3. **Documentation**: Document your MCP capabilities in your package README
4. **Security**: Never include sensitive data or credentials in extension code
5. **Testing**: Test your extension in isolation before publishing
6. **Versioning**: Follow semantic versioning for your extension package

## Example Extension Packages

See the official Symfony AI Mate extensions for reference implementations:

- `symfony/ai-mate-tools` - Common development tools
- `symfony/ai-mate-resources` - Symfony-specific resources
- `symfony/ai-mate-prompts` - Pre-configured prompts for common tasks

## Troubleshooting

### Extension Not Discovered

Check that:
- Package type is exactly `"ai-mate-extension"`
- `composer install` has been run
- Package appears in `vendor/composer/installed.json`

### Extension Not Loaded

Verify:
- Package is listed in `.mcp.php` `enabled_plugins`
- Scan directories exist and contain PHP files
- MCP attribute classes are imported correctly

### Capabilities Not Found

Ensure:
- PHP classes use correct MCP SDK attributes
- Scan directories are configured correctly in composer.json
- Files are in the configured scan directories
