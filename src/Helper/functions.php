<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/*
 * Initialize a global registry for disabled MCP features.
 */
if (!isset($GLOBALS['ai_mate_mcp_disabled_features'])) {
    $GLOBALS['ai_mate_mcp_disabled_features'] = [];
}

/**
 * Disable a specific MCP feature from an extension.
 *
 * This function allows you to disable specific tools, resources, prompts, or
 * resource templates from MCP extensions at a granular level.
 *
 * Example usage in .mate/services.php:
 * ```php
 * TODO this is not how the .mate/services.php works. We need to remove this file and use parameters
 * mateDisableFeature('symfony/ai-mate', 'tool', 'php-version');
 * mateDisableFeature('vendor/extension', 'resource', 'some-resource');
 * mateDisableFeature('vendor/extension', 'prompt', 'my-prompt');
 * mateDisableFeature('vendor/extension', 'resourceTemplate', 'template-pattern');
 * ```
 *
 * @throws InvalidArgumentException If the feature type is invalid
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
function mateDisableFeature(string $extension, string $type, string $name): void
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
function mateGetDisabledFeatures(string $extension): array
{
    /** @var array<string, array<string>> $registry */
    $registry = $GLOBALS['ai_mate_mcp_disabled_features'];

    return $registry[$extension] ?? [];
}
