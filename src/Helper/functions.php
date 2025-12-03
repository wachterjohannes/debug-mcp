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
 * Initialize a global registry for disabled MCP features.
 */
if (!isset($GLOBALS['ai_mate_mcp_disabled_features'])) {
    $GLOBALS['ai_mate_mcp_disabled_features'] = [];
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
 * @throws RuntimeException If the .mate/ directory or the specified file does not exist
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
function mcpLoadEnv(string $filename): void
{
    // Get the root directory from environment variable set in bin/mate.php
    $rootDir = $_ENV['MATE_ROOT_DIR'] ?? '';
    if ('' === $rootDir || !\is_string($rootDir)) {
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
 * This function allows you to disable specific tools, resources, prompts, or
 * resource templates from MCP extensions at a granular level.
 *
 * Example usage in .mate/services.php:
 * ```php
 * mcpDisableFeature('symfony/ai-mate', 'tool', 'php-version');
 * mcpDisableFeature('vendor/extension', 'resource', 'some-resource');
 * mcpDisableFeature('vendor/extension', 'prompt', 'my-prompt');
 * mcpDisableFeature('vendor/extension', 'resourceTemplate', 'template-pattern');
 * ```
 *
 * @throws InvalidArgumentException If the feature type is invalid
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
function mcpDisableFeature(string $extension, string $type, string $name): void
{
    // Validate feature type
    $validTypes = ['tool', 'resource', 'prompt', 'resourceTemplate'];
    if (!in_array($type, $validTypes, true)) {
        throw new InvalidArgumentException(sprintf('Invalid feature type "%s". Expected one of: %s', $type, implode(', ', $validTypes)));
    }

    /** @var array<string, array<string>> $registry */
    $registry = $GLOBALS['ai_mate_mcp_disabled_features'];

    if (!isset($registry[$extension])) {
        $registry[$extension] = [];
    }

    $featureId = $type.'.'.$name;
    $registry[$extension][] = $featureId;
    $GLOBALS['ai_mate_mcp_disabled_features'] = $registry;
}

/**
 * Get all disabled features for an extension.
 *
 * @return string[] List of disabled features
 *
 * @internal
 */
function mcpGetDisabledFeatures(string $extension): array
{
    /** @var array<string, array<string>> $registry */
    $registry = $GLOBALS['ai_mate_mcp_disabled_features'];

    return $registry[$extension] ?? [];
}
