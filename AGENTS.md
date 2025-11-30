# AGENTS.md

AI agent guidance for the Chat component.

## Component Overview

AI mate is a guidance document for the AI Mate component, designed for use with Claude Code and other Coding Agents.

This component provides an MCP (Model Context Protocol) server implementation that enables AI coding assistants to interact with your development environment through tools, resources, and prompts.

## Architecture

### Core Classes

- **`src/App.php`** - Symfony Console application builder
- **`src/Command/ServeCommand.php`** - Starts the MCP server with STDIO transport
- **`src/Discovery/DiscoveryManager.php`** - Coordinates extension discovery from multiple sources
- **`src/Discovery/ComposerDiscovery.php`** - Scans vendor packages for MCP extensions via composer.json extra section
- **`src/Discovery/LocalDiscovery.php`** - Scans local `mcp/` directory using reflection for MCP attributes

### Key Features

The component supports MCP capabilities through attribute-based discovery:

- **Tools** - Executable functions exposed to AI assistants via `#[McpTool]` attribute
- **Resources** - Data sources and templates via `#[McpResource]` attribute
- **Prompts** - Pre-configured prompts via `#[McpPrompt]` attribute

Extensions can be:
- **Vendor packages** - Declared in composer.json extra section, must be whitelisted in `.mcp.php`
- **Local files** - PHP files in `mcp/` directory, automatically enabled

## Essential Commands

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

## Development Notes

- All new classes should have `@author` tags
- Use component-specific exceptions from `src/Exception/`
- Follow Symfony coding standards with `@Symfony` PHP CS Fixer rules
- The component is marked as experimental and subject to BC breaks
