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

    public function testMcpDisableFeaturesCreatesFilterWithExcludedFeatures(): void
    {
        // Set up global registry
        mcpDisableFeature('vendor/package', 'tool.analyze');
        mcpDisableFeature('vendor/package', 'resource.config');

        $filter = PluginFilter::all()->withDisabledFeatures('vendor/package');

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($filter->allowsFeature('resource', 'config'));
        $this->assertTrue($filter->allowsFeature('tool', 'other'));
    }

    public function testMcpDisableFeatureAcceptsSingleFeature(): void
    {
        // Set up global registry
        mcpDisableFeature('vendor/package', 'tool.analyze');

        $filter = PluginFilter::all()->withDisabledFeatures('vendor/package');

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
        // Set up global registry
        mcpDisableFeature('vendor/package', 'tool.analyze');
        mcpDisableFeature('vendor/package', 'resource.config');

        $filter = PluginFilter::all()->withDisabledFeatures('vendor/package');

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($filter->allowsFeature('resource', 'config'));
    }

    public function testAllowsFeatureReturnsTrueForNonExcludedFeature(): void
    {
        // Set up global registry
        mcpDisableFeature('vendor/package', 'tool.analyze');

        $filter = PluginFilter::all()->withDisabledFeatures('vendor/package');

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

    public function testWithDisabledFeaturesMergesMultipleDisabledFeatures(): void
    {
        // Set up global registry with multiple features
        mcpDisableFeature('vendor/package', 'tool.tool1');
        mcpDisableFeature('vendor/package', 'tool.tool2');
        mcpDisableFeature('vendor/package', 'resource.resource1');

        $filter = PluginFilter::all()->withDisabledFeatures('vendor/package');

        // All disabled features should be filtered
        $this->assertFalse($filter->allowsFeature('tool', 'tool1'));
        $this->assertFalse($filter->allowsFeature('tool', 'tool2'));
        $this->assertFalse($filter->allowsFeature('resource', 'resource1'));
        $this->assertTrue($filter->allowsFeature('tool', 'other-tool'));
    }

    public function testClassFilteringAlwaysReturnsTrue(): void
    {
        // Set up global registry
        mcpDisableFeature('vendor/package', 'tool.my-tool');

        $filter = PluginFilter::all()->withDisabledFeatures('vendor/package');

        // Class filtering has been removed - all classes are allowed
        $this->assertTrue($filter->allows('SomeClass'));
        $this->assertTrue($filter->allows('OtherClass'));
        $this->assertTrue($filter->allows('AnyClass'));

        // Feature filtering should still work
        $this->assertFalse($filter->allowsFeature('tool', 'my-tool'));
        $this->assertTrue($filter->allowsFeature('tool', 'other-tool'));
    }
}
