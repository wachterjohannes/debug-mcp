# Local MCP server for development

This is a PHP tool to run a local server that will help your Jetbrains AI, Claude, Github Copilot,
Cursor or whatever AI tool you use to be more efficient and correct.

This is the core package that will create an manage your server. It includes some standard tools.
Framework or project specific tools will live in their own packages.

## Usage

Install with composer:

```bash
composer require symfony/package-name
```

See how to integrate your tool in the [integration guide](integration.md).

## How to add features?

The easiest way is to create a folder next to your `src` and `test` directory. And add classes with
`#[McpTool]`.

## Configuration

Explain how we add 3rd party tools and local tools.
