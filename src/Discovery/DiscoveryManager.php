<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Discovery;

/**
 * Coordinates discovery of MCP extensions from multiple sources.
 *
 * Three-phase discovery mechanism:
 * 1. Composer Package Discovery - from vendor/composer/installed.json
 * 2. Extra Config Discovery - from root composer.json extra section
 * 3. Local Directory Discovery - from mcp/ directory with reflection
 */
class DiscoveryManager
{
    private ComposerDiscovery $composerDiscovery;
    private LocalDiscovery $localDiscovery;
    private string $rootDir;

    public function __construct(
        ?ComposerDiscovery $composerDiscovery = null,
        ?LocalDiscovery $localDiscovery = null,
        ?string $rootDir = null
    ) {
        $this->rootDir = $rootDir ?? getcwd();
        $this->composerDiscovery = $composerDiscovery ?? new ComposerDiscovery($this->rootDir . '/vendor');
        $this->localDiscovery = $localDiscovery ?? new LocalDiscovery($this->rootDir . '/mcp');
    }

    /**
     * Discover all MCP extension classes from all sources.
     *
     * @return array<string> Unique fully-qualified class names
     */
    public function discover(): array
    {
        $classes = [];

        // Phase 1: Vendor packages
        $classes = array_merge($classes, $this->composerDiscovery->scan());

        // Phase 2: Extra config
        $classes = array_merge($classes, $this->readExtraConfig());

        // Phase 3: Local directory
        $classes = array_merge($classes, $this->localDiscovery->scan());

        return array_values(array_unique($classes));
    }

    /**
     * Read extension classes from root composer.json extra section.
     *
     * @return array<string>
     */
    private function readExtraConfig(): array
    {
        $composerJsonPath = $this->rootDir . '/composer.json';

        if (! file_exists($composerJsonPath)) {
            return [];
        }

        $composerData = json_decode(file_get_contents($composerJsonPath), true);
        if (! is_array($composerData)) {
            return [];
        }

        $extra = $composerData['extra'] ?? [];
        $mcpConfig = $extra['wachterjohannes/debug-mcp'] ?? null;

        if (! is_array($mcpConfig)) {
            return [];
        }

        $classes = $mcpConfig['classes'] ?? [];

        return is_array($classes) ? $classes : [];
    }
}
