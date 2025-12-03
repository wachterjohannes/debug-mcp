<?php

namespace Symfony\AI\Mate\Tests\Model;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Mate\Model\PluginFilter;

class PluginFilterFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear global registry before each test
        $GLOBALS['mcp_disabled_features'] = [];
    }

    protected function tearDown(): void
    {
        // Clean up global registry after each test
        $GLOBALS['mcp_disabled_features'] = [];
    }

    public function testExcludeFeaturesCreatesFilterWithExcludedFeatures(): void
    {
        $filter = PluginFilter::excludeFeatures(['tool.analyze', 'resource.config']);

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($filter->allowsFeature('resource', 'config'));
        $this->assertTrue($filter->allowsFeature('tool', 'other'));
    }

    public function testExcludeFeaturesAcceptsSingleString(): void
    {
        $filter = PluginFilter::excludeFeatures('tool.analyze');

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertTrue($filter->allowsFeature('tool', 'other'));
    }

    public function testAllowsFeatureReturnsTrueWhenNoFeaturesExcluded(): void
    {
        $filter = PluginFilter::all();

        $this->assertTrue($filter->allowsFeature('tool', 'any-tool'));
        $this->assertTrue($filter->allowsFeature('resource', 'any-resource'));
        $this->assertTrue($filter->allowsFeature('prompt', 'any-prompt'));
    }

    public function testAllowsFeatureReturnsFalseForExcludedFeature(): void
    {
        $filter = PluginFilter::excludeFeatures(['tool.analyze', 'resource.config']);

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($filter->allowsFeature('resource', 'config'));
    }

    public function testAllowsFeatureReturnsTrueForNonExcludedFeature(): void
    {
        $filter = PluginFilter::excludeFeatures(['tool.analyze']);

        $this->assertTrue($filter->allowsFeature('tool', 'other-tool'));
        $this->assertTrue($filter->allowsFeature('resource', 'any-resource'));
    }

    public function testWithDisabledFeaturesReturnsNewFilterWithGlobalRegistry(): void
    {
        // Set up global registry
        mcpDisableFeature('vendor/package', 'tool.analyze');
        mcpDisableFeature('vendor/package', 'resource.config');

        $originalFilter = PluginFilter::all();
        $newFilter = $originalFilter->withDisabledFeatures('vendor/package');

        // Original filter should be unchanged
        $this->assertTrue($originalFilter->allowsFeature('tool', 'analyze'));
        $this->assertTrue($originalFilter->allowsFeature('resource', 'config'));

        // New filter should have disabled features
        $this->assertFalse($newFilter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($newFilter->allowsFeature('resource', 'config'));
    }

    public function testWithDisabledFeaturesReturnsSameFilterWhenNoDisabledFeatures(): void
    {
        $filter = PluginFilter::all();
        $newFilter = $filter->withDisabledFeatures('vendor/unknown');

        $this->assertSame($filter, $newFilter);
    }

    public function testWithDisabledFeaturesMergesWithExistingExclusions(): void
    {
        // Set up global registry
        mcpDisableFeature('vendor/package', 'tool.global-tool');

        $filter = PluginFilter::excludeFeatures(['tool.local-tool']);
        $newFilter = $filter->withDisabledFeatures('vendor/package');

        // Both local and global exclusions should be applied
        $this->assertFalse($newFilter->allowsFeature('tool', 'local-tool'));
        $this->assertFalse($newFilter->allowsFeature('tool', 'global-tool'));
        $this->assertTrue($newFilter->allowsFeature('tool', 'other-tool'));
    }

    public function testFeatureFilteringIsIndependentFromClassFiltering(): void
    {
        $filter = PluginFilter::exclude(['SomeClass'])
            ->withDisabledFeatures('vendor/package');

        mcpDisableFeature('vendor/package', 'tool.my-tool');

        $newFilter = $filter->withDisabledFeatures('vendor/package');

        // Class filtering should still work
        $this->assertFalse($newFilter->allows('SomeClass'));
        $this->assertTrue($newFilter->allows('OtherClass'));

        // Feature filtering should also work
        $this->assertFalse($newFilter->allowsFeature('tool', 'my-tool'));
        $this->assertTrue($newFilter->allowsFeature('tool', 'other-tool'));
    }
}
