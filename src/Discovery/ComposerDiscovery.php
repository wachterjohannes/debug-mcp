<?php

namespace Symfony\AI\Mate\Discovery;

/**
 * Discovers MCP extensions from installed Composer packages.
 *
 * Scans vendor/composer/installed.json for packages that declare
 * extension classes in their extra.symfony/ai-mate.classes configuration.
 */
class ComposerDiscovery
{
    private string $vendorDir;

    public function __construct(?string $vendorDir = null)
    {
        $this->vendorDir = $vendorDir ?? dirname(__DIR__, 2) . '/vendor';
    }

    /**
     * Scan installed Composer packages for MCP extension classes.
     *
     * @return array<string> Fully-qualified class names
     */
    public function scan(): array
    {
        $installedJsonPath = $this->vendorDir . '/composer/installed.json';

        if (! file_exists($installedJsonPath)) {
            return [];
        }

        $installedData = json_decode(file_get_contents($installedJsonPath), true);
        if (! is_array($installedData)) {
            return [];
        }

        $classes = [];
        $packages = $installedData['packages'] ?? $installedData;

        foreach ($packages as $package) {
            if (! is_array($package)) {
                continue;
            }

            $extra = $package['extra'] ?? [];
            $mcpConfig = $extra['symfony/ai-mate'] ?? null;

            if (! is_array($mcpConfig)) {
                continue;
            }

            $packageClasses = $mcpConfig['classes'] ?? [];
            if (is_array($packageClasses)) {
                $classes = array_merge($classes, $packageClasses);
            }
        }

        return array_values(array_unique($classes));
    }
}
