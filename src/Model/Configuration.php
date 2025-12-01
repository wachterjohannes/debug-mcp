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
use Symfony\Component\Dotenv\Dotenv;

final class Configuration
{
    /**
     * @param string[]                    $scanDirs
     * @param array<string, PluginFilter> $enabledPlugins
     */
    private function __construct(
        public readonly string $rootDir,
        public readonly string $cacheDir,
        public readonly array $scanDirs,
        public readonly array $enabledPlugins,
        public readonly string|null $envFile,
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
        /** @var string|null $cacheDir */
        $envFile = $config['envFile'];
        /** @var array<string> $scanDirs */
        $scanDirs = $config['scanDirs'];

        /** @var array<string|int, string|array{exclude?: string|string[], include_only?: string|string[]}> $rawPlugins */
        $rawPlugins = $config['enabledPlugins'] ?? [];
        $enabledPlugins = self::parseEnabledPlugins($rawPlugins);

        return new self(
            rootDir: $rootDir,
            cacheDir: $cacheDir,
            scanDirs: $scanDirs,
            enabledPlugins: $enabledPlugins,
            envFile: $envFile,
        );
    }

    /**
     * @param array<string|int, string|array{exclude?: string|string[], include_only?: string|string[]}> $config
     *
     * @return array<string, PluginFilter>
     *
     * @throws ConfigurationException
     */
    private static function parseEnabledPlugins(array $config): array
    {
        $plugins = [];

        foreach ($config as $key => $value) {
            // Simple string format: 'vendor/package'
            if (\is_string($value)) {
                $plugins[$value] = PluginFilter::all();
                continue;
            }

            // Array format: 'vendor/package' => ['exclude' => ...]
            if (\is_string($key)) {
                // @phpstan-ignore function.alreadyNarrowedType
                if (!\is_array($value)) {
                    throw new ConfigurationException(\sprintf('Invalid enabledPlugins entry for "%s". Expected array', $key));
                }
                $plugins[$key] = self::parsePluginFilter($key, $value);
                continue;
            }

            throw new ConfigurationException(\sprintf('Invalid enabledPlugins entry at index "%s". Expected string or "package-name" => array', $key));
        }

        return $plugins;
    }

    /**
     * @param array<mixed> $filter
     *
     * @throws ConfigurationException
     */
    private static function parsePluginFilter(string $packageName, array $filter): PluginFilter
    {
        $hasExclude = \array_key_exists('exclude', $filter);
        $hasIncludeOnly = \array_key_exists('include_only', $filter);

        if ($hasExclude && $hasIncludeOnly) {
            throw new ConfigurationException(\sprintf('Plugin "%s" cannot have both "exclude" and "include_only" options', $packageName));
        }

        if ($hasExclude) {
            $exclude = $filter['exclude'];
            if (\is_string($exclude)) {
                return PluginFilter::exclude($exclude);
            }
            if (\is_array($exclude)) {
                // @phpstan-ignore argument.type
                return PluginFilter::exclude($exclude);
            }
            throw new ConfigurationException(\sprintf('Plugin "%s" exclude option must be string or array of strings', $packageName));
        }

        if ($hasIncludeOnly) {
            $includeOnly = $filter['include_only'];
            if (\is_string($includeOnly)) {
                return PluginFilter::includeOnly($includeOnly);
            }
            if (\is_array($includeOnly)) {
                // @phpstan-ignore argument.type
                return PluginFilter::includeOnly($includeOnly);
            }
            throw new ConfigurationException(\sprintf('Plugin "%s" include_only option must be string or array of strings', $packageName));
        }

        // Empty array means include all
        return PluginFilter::all();
    }

    /**
     * @param array<string, mixed> $config
     *
     * @throws ConfigurationException If the configuration is invalid
     */
    private static function validate(array $config): void
    {
        $requiredKeys = ['rootDir', 'cacheDir', 'scanDirs', 'envFile'];
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

        if ((!\is_string($config['envFile']) && null !== $config['envFile']) || $config['envFile'] === '') {
            throw new ConfigurationException('Configuration key "envFile" must be a non-empty string or null');
        }
        if (null !== $config['envFile'] && !class_exists(Dotenv::class)) {
            throw new ConfigurationException('Configuring key "envFile" requires symfony/dotenv package. Try running "composer require symfony/dotenv".');
        }

        if (!\is_array($config['scanDirs'])) {
            throw new ConfigurationException('Configuration key "scanDirs" must be an array');
        }

        foreach ($config['scanDirs'] as $index => $scanDir) {
            if (!\is_string($scanDir) || '' === $scanDir) {
                throw new ConfigurationException(\sprintf('Configuration key "scanDirs[%s]" must be a non-empty string', $index));
            }
        }

        if (isset($config['enabledPlugins']) && !\is_array($config['enabledPlugins'])) {
            throw new ConfigurationException('Configuration key "enabledPlugins" must be an array');
        }
    }
}
