<?php

namespace Symfony\AI\Mate\Tests\Model;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Mate\Model\ExtensionFilter;

class ExtensionFilterFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear global registry before each test
        $GLOBALS['ai_mate_mcp_disabled_features'] = [];
    }

    protected function tearDown(): void
    {
        // Clean up global registry after each test
        $GLOBALS['ai_mate_mcp_disabled_features'] = [];
    }

    public function testMcpDisableFeaturesCreatesFilterWithExcludedFeatures(): void
    {
        mcpDisableFeature('vendor/package', 'tool', 'analyze');
        mcpDisableFeature('vendor/package', 'resource', 'config');

        $filter = ExtensionFilter::all()->withDisabledFeatures('vendor/package');

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($filter->allowsFeature('resource', 'config'));
        $this->assertTrue($filter->allowsFeature('tool', 'other'));
    }

    public function testMcpDisableFeatureAcceptsSingleFeature(): void
    {
        mcpDisableFeature('vendor/package', 'tool', 'analyze');

        $filter = ExtensionFilter::all()->withDisabledFeatures('vendor/package');

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertTrue($filter->allowsFeature('tool', 'other'));
    }

    public function testAllowsFeatureReturnsTrueWhenNoFeaturesExcluded(): void
    {
        $filter = ExtensionFilter::all();

        $this->assertTrue($filter->allowsFeature('tool', 'any-tool'));
        $this->assertTrue($filter->allowsFeature('resource', 'any-resource'));
        $this->assertTrue($filter->allowsFeature('prompt', 'any-prompt'));
    }

    public function testAllowsFeatureReturnsFalseForExcludedFeature(): void
    {
        mcpDisableFeature('vendor/package', 'tool', 'analyze');
        mcpDisableFeature('vendor/package', 'resource', 'config');

        $filter = ExtensionFilter::all()->withDisabledFeatures('vendor/package');

        $this->assertFalse($filter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($filter->allowsFeature('resource', 'config'));
    }

    public function testAllowsFeatureReturnsTrueForNonExcludedFeature(): void
    {
        mcpDisableFeature('vendor/package', 'tool', 'analyze');

        $filter = ExtensionFilter::all()->withDisabledFeatures('vendor/package');

        $this->assertTrue($filter->allowsFeature('tool', 'other-tool'));
        $this->assertTrue($filter->allowsFeature('resource', 'any-resource'));
    }

    public function testWithDisabledFeaturesReturnsNewFilterWithGlobalRegistry(): void
    {
        mcpDisableFeature('vendor/package', 'tool', 'analyze');
        mcpDisableFeature('vendor/package', 'resource', 'config');

        $originalFilter = ExtensionFilter::all();
        $newFilter = $originalFilter->withDisabledFeatures('vendor/package');

        $this->assertTrue($originalFilter->allowsFeature('tool', 'analyze'));
        $this->assertTrue($originalFilter->allowsFeature('resource', 'config'));

        // New filter should have disabled features
        $this->assertFalse($newFilter->allowsFeature('tool', 'analyze'));
        $this->assertFalse($newFilter->allowsFeature('resource', 'config'));
    }

    public function testWithDisabledFeaturesReturnsSameFilterWhenNoDisabledFeatures(): void
    {
        $filter = ExtensionFilter::all();
        $newFilter = $filter->withDisabledFeatures('vendor/unknown');

        $this->assertSame($filter, $newFilter);
    }

    public function testWithDisabledFeaturesMergesMultipleDisabledFeatures(): void
    {
        mcpDisableFeature('vendor/package', 'tool', 'tool1');
        mcpDisableFeature('vendor/package', 'tool', 'tool2');
        mcpDisableFeature('vendor/package', 'resource', 'resource1');

        $filter = ExtensionFilter::all()->withDisabledFeatures('vendor/package');

        $this->assertFalse($filter->allowsFeature('tool', 'tool1'));
        $this->assertFalse($filter->allowsFeature('tool', 'tool2'));
        $this->assertFalse($filter->allowsFeature('resource', 'resource1'));
        $this->assertTrue($filter->allowsFeature('tool', 'other-tool'));
    }
}
