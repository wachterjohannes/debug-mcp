<?php

namespace Symfony\AI\Mate\Tests\Helper;

use PHPUnit\Framework\TestCase;

class McpDisableFeatureTest extends TestCase
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

    public function testDisablesTool(): void
    {
        mcpDisableFeature('vendor/package', 'tool.my-tool');

        $disabledFeatures = mcpGetDisabledFeatures('vendor/package');

        $this->assertCount(1, $disabledFeatures);
        $this->assertContains('tool.my-tool', $disabledFeatures);
    }

    public function testDisablesResource(): void
    {
        mcpDisableFeature('vendor/package', 'resource.my-resource');

        $disabledFeatures = mcpGetDisabledFeatures('vendor/package');

        $this->assertCount(1, $disabledFeatures);
        $this->assertContains('resource.my-resource', $disabledFeatures);
    }

    public function testDisablesPrompt(): void
    {
        mcpDisableFeature('vendor/package', 'prompt.my-prompt');

        $disabledFeatures = mcpGetDisabledFeatures('vendor/package');

        $this->assertCount(1, $disabledFeatures);
        $this->assertContains('prompt.my-prompt', $disabledFeatures);
    }

    public function testDisablesMultipleFeaturesForSameExtension(): void
    {
        mcpDisableFeature('vendor/package', 'tool.tool1');
        mcpDisableFeature('vendor/package', 'tool.tool2');
        mcpDisableFeature('vendor/package', 'resource.resource1');

        $disabledFeatures = mcpGetDisabledFeatures('vendor/package');

        $this->assertCount(3, $disabledFeatures);
        $this->assertContains('tool.tool1', $disabledFeatures);
        $this->assertContains('tool.tool2', $disabledFeatures);
        $this->assertContains('resource.resource1', $disabledFeatures);
    }

    public function testDisablesFeaturesForDifferentExtensions(): void
    {
        mcpDisableFeature('vendor/package-a', 'tool.tool1');
        mcpDisableFeature('vendor/package-b', 'tool.tool2');

        $disabledFeaturesA = mcpGetDisabledFeatures('vendor/package-a');
        $disabledFeaturesB = mcpGetDisabledFeatures('vendor/package-b');

        $this->assertCount(1, $disabledFeaturesA);
        $this->assertContains('tool.tool1', $disabledFeaturesA);

        $this->assertCount(1, $disabledFeaturesB);
        $this->assertContains('tool.tool2', $disabledFeaturesB);
    }

    public function testReturnsEmptyArrayForExtensionWithNoDisabledFeatures(): void
    {
        $disabledFeatures = mcpGetDisabledFeatures('vendor/unknown');

        $this->assertCount(0, $disabledFeatures);
    }

    public function testThrowsExceptionForInvalidFeatureFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid feature format');

        mcpDisableFeature('vendor/package', 'invalid-format');
    }

    public function testThrowsExceptionForInvalidFeatureType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid feature format');

        mcpDisableFeature('vendor/package', 'invalid.feature-name');
    }

    public function testAcceptsFeatureNamesWithHyphens(): void
    {
        mcpDisableFeature('vendor/package', 'tool.my-tool-name');

        $disabledFeatures = mcpGetDisabledFeatures('vendor/package');

        $this->assertContains('tool.my-tool-name', $disabledFeatures);
    }

    public function testAcceptsFeatureNamesWithUnderscores(): void
    {
        mcpDisableFeature('vendor/package', 'tool.my_tool_name');

        $disabledFeatures = mcpGetDisabledFeatures('vendor/package');

        $this->assertContains('tool.my_tool_name', $disabledFeatures);
    }

    public function testAcceptsFeatureNamesWithNumbers(): void
    {
        mcpDisableFeature('vendor/package', 'tool.tool123');

        $disabledFeatures = mcpGetDisabledFeatures('vendor/package');

        $this->assertContains('tool.tool123', $disabledFeatures);
    }
}
