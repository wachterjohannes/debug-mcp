<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Dotenv\Dotenv;

/*
 * Initialize global registry for disabled MCP features.
 */
if (!isset($GLOBALS['mcp_disabled_features'])) {
    $GLOBALS['mcp_disabled_features'] = [];
}

/**
 * Load environment variables from a file in the .mate/ directory.
 *
 * This helper function loads environment variables using the Symfony Dotenv component.
 * Paths are resolved relative to the .mate/ directory.
 *
 * Example usage in .mate/services.php:
 * ```php
 * mcpLoadEnv('.env'); // Loads .mate/.env
 * ```
 *
 * @param string $filename The filename relative to .mate/ directory
 *
 * @throws RuntimeException If the .mate/ directory or the specified file does not exist
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
function mcpLoadEnv(string $filename): void
{
    // Get the root directory from environment variable set in bin/mate.php
    $rootDir = getenv('MATE_ROOT_DIR');
    if (false === $rootDir || '' === $rootDir) {
        throw new RuntimeException('MATE_ROOT_DIR environment variable is not set. This function must be called after bootstrap.');
    }

    $mateDir = $rootDir.'/.mate';
    if (!is_dir($mateDir)) {
        throw new RuntimeException(sprintf('The .mate directory does not exist: %s', $mateDir));
    }

    $envFile = $mateDir.'/'.ltrim($filename, '/');
    if (!file_exists($envFile)) {
        throw new RuntimeException(sprintf('The environment file does not exist: %s', $envFile));
    }

    (new Dotenv())->load($envFile);
}

/**
 * Disable a specific MCP feature from an extension.
 *
 * This function allows you to disable specific tools, resources, or prompts
 * from MCP extensions at a granular level. The feature is specified using
 * the format "{type}.{name}" where type is one of: tool, resource, prompt.
 *
 * Example usage in .mate/services.php:
 * ```php
 * mcpDisableFeature('phpstan/ai-mate-extension', 'tool.phpstan-analyze');
 * mcpDisableFeature('vendor/extension', 'resource.some-resource');
 * mcpDisableFeature('vendor/extension', 'prompt.my-prompt');
 * ```
 *
 * @param string $extension The extension identifier (e.g., 'vendor/package')
 * @param string $feature   The feature identifier in format "{type}.{name}"
 *
 * @throws InvalidArgumentException If the feature format is invalid
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
function mcpDisableFeature(string $extension, string $feature): void
{
    // Validate feature format
    if (!preg_match('/^(tool|resource|prompt)\.[\w\-]+$/', $feature)) {
        throw new InvalidArgumentException(sprintf('Invalid feature format "%s". Expected format: "{type}.{name}" where type is one of: tool, resource, prompt', $feature));
    }

    /** @var array<string, array<string>> $registry */
    $registry = $GLOBALS['mcp_disabled_features'];

    if (!isset($registry[$extension])) {
        $registry[$extension] = [];
    }

    $registry[$extension][] = $feature;
    $GLOBALS['mcp_disabled_features'] = $registry;
}

/**
 * Get all disabled features for an extension.
 *
 * @param string $extension The extension identifier
 *
 * @return string[] List of disabled features
 *
 * @internal
 */
function mcpGetDisabledFeatures(string $extension): array
{
    /** @var array<string, array<string>> $registry */
    $registry = $GLOBALS['mcp_disabled_features'];

    return $registry[$extension] ?? [];
}
