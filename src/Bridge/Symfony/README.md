# Symfony Bridge for AI Mate

This bridge provides MCP tools for inspecting Symfony applications.

## Features

- **Service Container Inspection**: List all registered services from the Symfony container
- **Auto-detection**: Automatically finds container XML in cache directories (dev/test/prod)

## Available Tools

### symfony-services

Get a list of all Symfony services registered in the container.

```
Returns: array<string, class-string|null>
  - Key: Service ID
  - Value: Service class (or null for aliases)
```

## Configuration

Configure the cache directory in your service configuration:

```php
// config/services.php
$configurator->parameters()
    ->set('ai_mate_symfony.cache_dir', '%root_dir%/var/cache');
```

The tool will search for `App_KernelDevDebugContainer.xml` in:
- `{cache_dir}/`
- `{cache_dir}/dev/`
- `{cache_dir}/test/`
- `{cache_dir}/prod/`

## Installation

The bridge is automatically discovered when installed as a Composer package with `extra.ai-mate` configuration.

```bash
composer require symfony/ai-mate-symfony
vendor/bin/mate init
vendor/bin/mate discover
```

The `discover` command will automatically add it to `.mate/extensions.php`:

```php
return [
    'symfony/ai-mate-symfony' => ['enabled' => true],
];
```

## Resources

- [Contributing](https://symfony.com/doc/current/contributing/index.html)
- [Report issues](https://github.com/symfony/ai/issues) and
  [send Pull Requests](https://github.com/symfony/ai/pulls)
  in the [main Symfony AI repository](https://github.com/symfony/ai)
