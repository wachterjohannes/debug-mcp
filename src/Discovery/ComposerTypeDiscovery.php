<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Mate\Discovery;

use Psr\Log\LoggerInterface;

/**
 * Discovers MCP extensions via Composer package type.
 *
 * Extensions must declare themselves in composer.json:
 * {
 *   "type": "ai-mate-extension",
 *   "extra": {
 *     "ai-mate": {
 *       "scan-dirs": ["src/Mcp"]
 *     }
 *   }
 * }
 */
final class ComposerTypeDiscovery
{
    private const PACKAGE_TYPE = 'ai-mate-extension';

    /**
     * @var array<string, array{
     *     name: string,
     *     type: string,
     *     extra?: array<string, mixed>,
     * }>|null
     */
    private ?array $installedPackages = null;

    public function __construct(
        private string $rootDir,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string[] $enabledPlugins
     *
     * @return array<string, string[]> Package name => scan directories
     */
    public function discover(array $enabledPlugins = []): array
    {
        $installed = $this->getInstalledPackages();
        $extensions = [];

        foreach ($installed as $package) {
            if (self::PACKAGE_TYPE !== $package['type']) {
                continue;
            }

            $packageName = $package['name'];

            if ([] !== $enabledPlugins && !\in_array($packageName, $enabledPlugins, true)) {
                $this->logger->debug('Skipping non-whitelisted extension', ['package' => $packageName]);

                continue;
            }

            $scanDirs = $this->extractScanDirs($package, $packageName);
            if ([] !== $scanDirs) {
                $extensions[$packageName] = $scanDirs;
            }
        }

        return $extensions;
    }

    /**
     * @return array<string, array{
     *     name: string,
     *     type: string,
     *     extra?: array<string, mixed>,
     * }>
     */
    private function getInstalledPackages(): array
    {
        if (null !== $this->installedPackages) {
            return $this->installedPackages;
        }

        $installedJsonPath = $this->rootDir.'/vendor/composer/installed.json';
        if (!file_exists($installedJsonPath)) {
            $this->logger->warning('Composer installed.json not found', ['path' => $installedJsonPath]);

            return $this->installedPackages = [];
        }

        $content = file_get_contents($installedJsonPath);
        if (false === $content) {
            $this->logger->warning('Could not read installed.json', ['path' => $installedJsonPath]);

            return $this->installedPackages = [];
        }

        try {
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Invalid JSON in installed.json', ['error' => $e->getMessage()]);

            return $this->installedPackages = [];
        }

        if (!\is_array($data)) {
            return $this->installedPackages = [];
        }

        // Handle both formats: {"packages": [...]} and direct array
        $packages = $data['packages'] ?? $data;
        if (!\is_array($packages)) {
            return $this->installedPackages = [];
        }

        $indexed = [];
        foreach ($packages as $package) {
            if (!\is_array($package) || !isset($package['name']) || !\is_string($package['name'])) {
                continue;
            }

            if (!isset($package['type']) || !\is_string($package['type'])) {
                continue;
            }

            /** @var array{
             *     name: string,
             *     type: string,
             *     extra?: array<string, mixed>,
             * } $validPackage */
            $validPackage = [
                'name' => $package['name'],
                'type' => $package['type'],
            ];

            if (isset($package['extra']) && \is_array($package['extra'])) {
                /** @var array<string, mixed> $extra */
                $extra = $package['extra'];
                $validPackage['extra'] = $extra;
            }

            $indexed[$package['name']] = $validPackage;
        }

        return $this->installedPackages = $indexed;
    }

    /**
     * @param array{
     *     name: string,
     *     type: string,
     *     extra?: array<string, mixed>,
     * } $package
     *
     * @return string[]
     */
    private function extractScanDirs(array $package, string $packageName): array
    {
        $extra = $package['extra'] ?? [];

        $aiMateConfig = $extra['ai-mate'] ?? null;
        if (null === $aiMateConfig) {
            // Default: scan src/Mcp directory if no config provided
            $defaultDir = 'vendor/'.$packageName.'/src/Mcp';
            if (is_dir($this->rootDir.'/'.$defaultDir)) {
                return [$defaultDir];
            }

            $this->logger->warning('No ai-mate config and default directory not found', [
                'package' => $packageName,
                'default' => $defaultDir,
            ]);

            return [];
        }

        if (!\is_array($aiMateConfig)) {
            $this->logger->warning('Invalid ai-mate config in package', ['package' => $packageName]);

            return [];
        }

        $scanDirs = $aiMateConfig['scan-dirs'] ?? [];
        if (!\is_array($scanDirs)) {
            $this->logger->warning('Invalid scan-dirs in ai-mate config', ['package' => $packageName]);

            return [];
        }

        $validDirs = [];
        foreach ($scanDirs as $dir) {
            if (!\is_string($dir) || '' === trim($dir)) {
                continue;
            }

            $fullPath = 'vendor/'.$packageName.'/'.ltrim($dir, '/');
            if (!is_dir($this->rootDir.'/'.$fullPath)) {
                $this->logger->warning('Scan directory does not exist', [
                    'package' => $packageName,
                    'directory' => $fullPath,
                ]);
                continue;
            }

            $validDirs[] = $fullPath;
        }

        return $validDirs;
    }
}
