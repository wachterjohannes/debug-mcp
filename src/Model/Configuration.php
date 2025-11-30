<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Mate\Model;

use Symfony\AI\Mate\Exception\ConfigurationException;

final class Configuration
{
    /**
     * @param string[] $scanDirs
     * @param string[] $enabledPlugins
     */
    private function __construct(
        public readonly string $rootDir,
        public readonly string $cacheDir,
        public readonly array $scanDirs,
        public readonly array $enabledPlugins,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     *
     * @throws ConfigurationException If configuration is invalid
     */
    public static function fromArray(array $config): self
    {
        self::validate($config);

        /** @var string $rootDir */
        $rootDir = $config['rootDir'];
        /** @var string $cacheDir */
        $cacheDir = $config['cacheDir'];
        /** @var array<string> $scanDirs */
        $scanDirs = $config['scanDirs'];
        /** @var array<string> $enabledPlugins */
        $enabledPlugins = $config['enabled_plugins'] ?? [];

        return new self(
            rootDir: $rootDir,
            cacheDir: $cacheDir,
            scanDirs: $scanDirs,
            enabledPlugins: $enabledPlugins,
        );
    }

    /**
     * @throws ConfigurationException If key does not exist
     */
    public function get(string $key): mixed
    {
        if (!property_exists($this, $key)) {
            throw new ConfigurationException(\sprintf('Configuration key "%s" does not exist', $key));
        }

        return $this->{$key};
    }

    /**
     * @param array<string, mixed> $config
     *
     * @throws ConfigurationException If configuration is invalid
     */
    private static function validate(array $config): void
    {
        $requiredKeys = ['rootDir', 'cacheDir', 'scanDirs'];
        $missingKeys = array_diff($requiredKeys, array_keys($config));

        if ([] !== $missingKeys) {
            throw new ConfigurationException(\sprintf('Missing required configuration keys: %s', implode(', ', $missingKeys)));
        }

        if (!\is_string($config['rootDir']) || '' === $config['rootDir']) {
            throw new ConfigurationException('Configuration key "rootDir" must be a non-empty string');
        }

        if (!\is_string($config['cacheDir']) || '' === $config['cacheDir']) {
            throw new ConfigurationException('Configuration key "cacheDir" must be a non-empty string');
        }

        if (!\is_array($config['scanDirs'])) {
            throw new ConfigurationException('Configuration key "scanDirs" must be an array');
        }

        foreach ($config['scanDirs'] as $index => $scanDir) {
            if (!\is_string($scanDir) || '' === $scanDir) {
                throw new ConfigurationException(\sprintf('Configuration key "scanDirs[%s]" must be a non-empty string', $index));
            }
        }

        if (isset($config['enabled_plugins'])) {
            if (!\is_array($config['enabled_plugins'])) {
                throw new ConfigurationException('Configuration key "enabled_plugins" must be an array');
            }

            foreach ($config['enabled_plugins'] as $index => $plugin) {
                if (!\is_string($plugin) || '' === $plugin) {
                    throw new ConfigurationException(\sprintf('Configuration key "enabled_plugins[%s]" must be a non-empty string', $index));
                }
            }
        }
    }
}
