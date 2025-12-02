# Symfony AI Mate - Local MCP Server

This is a PHP tool that creates a local MCP server to enhance your AI development assistant (JetBrains AI, Claude, GitHub
Copilot, Cursor, etc.) with Symfony-specific knowledge and tools.

This is the core package that creates and manages your MCP server. It includes some standard tools, while framework or
project-specific tools live in their own packages.

## Usage

Install with composer:

```bash
composer require symfony/ai-mate
```

Initialize configuration:

```bash
vendor/bin/mate init
```

See how to integrate with your AI tool in the [integration guide](integration.md).

## How to add features?

The easiest way is to create a `mate` folder next to your `src` and `tests` directories, then add classes with `#[McpTool]`
attributes.

Example:

```php
<?php
// mate/MyTool.php
namespace App\Mate;

use Mcp\Capability\Attribute\McpTool;

class MyTool
{
    #[McpTool(name: 'my_tool', description: 'My custom tool')]
    public function execute(string $param): array
    {
        return ['result' => $param];
    }
}
```

## Configuration

Edit `.mate/extensions.php` in your project root to configure AI Mate:

```php
<?php
// .mate/extensions.php

return [
    // Whitelist vendor plugins (security: none enabled by default)
    'enabled_plugins' => [
        'vendor/package-name',
    ],

    // Local directories to scan (always enabled)
    'scanDir' => ['mate'],
];
```

### Adding Third-Party Tools

1. Install the package:
   ```bash
   composer require vendor/symfony-tools
   ```

2. Discover available tools:
   ```bash
   vendor/bin/mate discover
   ```

3. Add to `.mate/extensions.php`:
   ```php
   // .mate/extensions.php
   'enabled_plugins' => [
       'vendor/symfony-tools',
   ],
   ```

## Commands

### Init

Initialize `.mate/extensions.php` configuration file:

```bash
vendor/bin/mate init
```

### Run server

Start the MCP server:

```bash
vendor/bin/mate serve
```

### Discover MCP features

Find available MCP extensions in your vendor directory:

```bash
vendor/bin/mate discover
```

### Clear cache

Clear the MCP server cache:

```bash
vendor/bin/mate clear-cache
```

## Security

For security, no vendor plugins are enabled by default. You must explicitly whitelist packages in the `enabled_plugins`
configuration.

Local `mate/` directory is always enabled for rapid development.
