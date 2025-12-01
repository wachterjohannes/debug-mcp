# Monolog Bridge for AI Mate

This bridge provides MCP tools for searching and analyzing Monolog log files.

## Features

- **Auto-detect log format**: Supports both JSON and standard Monolog line format
- **Full-text search**: Search log entries by term
- **Regex search**: Search using regular expressions
- **Filter by level**: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY
- **Filter by channel**: Filter logs by their channel name
- **Date range filtering**: Search logs within a specific time period
- **Context field search**: Search by specific context field values
- **Tail functionality**: Get the most recent log entries

## Available Tools

### monolog-search

Search log entries by text term with optional filters.

```
Parameters:
- term (string, required): The search term
- level (string, optional): Filter by log level (e.g., ERROR, WARNING)
- channel (string, optional): Filter by channel name
- environment (string, optional): Filter by environment (e.g., dev, prod, test)
- from (string, optional): Start date (ISO 8601 format)
- to (string, optional): End date (ISO 8601 format)
- limit (int, optional): Maximum results (default: 100)
```

### monolog-search-regex

Search log entries using a regex pattern.

```
Parameters:
- pattern (string, required): Regex pattern (delimiters optional)
- level (string, optional): Filter by log level
- channel (string, optional): Filter by channel name
- environment (string, optional): Filter by environment (e.g., dev, prod, test)
- limit (int, optional): Maximum results (default: 100)
```

### monolog-context-search

Search logs by context field value.

```
Parameters:
- key (string, required): Context field name
- value (string, required): Value to search for
- level (string, optional): Filter by log level
- environment (string, optional): Filter by environment (e.g., dev, prod, test)
- limit (int, optional): Maximum results (default: 100)
```

### monolog-tail

Get the last N log entries.

```
Parameters:
- lines (int, optional): Number of lines (default: 50)
- level (string, optional): Filter by log level
- environment (string, optional): Filter by environment (e.g., dev, prod, test)
```

### monolog-list-files

List available log files with metadata.

```
Parameters:
- environment (string, optional): Filter by environment (e.g., dev, prod, test)
```

### monolog-list-channels

List all unique log channels found in log files.

### monolog-by-level

Get log entries filtered by level.

```
Parameters:
- level (string, required): Log level (DEBUG, INFO, WARNING, ERROR, etc.)
- environment (string, optional): Filter by environment (e.g., dev, prod, test)
- limit (int, optional): Maximum results (default: 100)
```

## Configuration

Configure the log directory in your service configuration:

```php
// config/services.php
$configurator->parameters()
    ->set('ai_mate_monolog.log_dir', '%kernel.project_dir%/var/log');
```

## Supported Log Formats

### JSON Format

```json
{"datetime":"2024-01-15T10:30:45+00:00","channel":"app","level":"ERROR","message":"Error message","context":{},"extra":{}}
```

### Line Format

```
[2024-01-15 10:30:45] app.ERROR: Error message {"context":"value"} {"extra":"value"}
```

## Installation

The bridge is automatically discovered when installed as a Composer package with type `ai-mate-extension`.

```bash
composer require symfony/ai-mate-monolog
```

Then enable it in your `.mcp.php` configuration:

```php
return [
    'enabled_plugins' => [
        'symfony/ai-mate-monolog',
    ],
];
```
