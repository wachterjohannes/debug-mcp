<?php

namespace Symfony\AI\Mate\Tests\Helper;

use PHPUnit\Framework\TestCase;

class MateDisableFeatureTest extends TestCase
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

    public function testDisablesTool(): void
    {
        mateDisableFeature('vendor/package', 'tool', 'my-tool');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertCount(1, $disabledFeatures);
        $this->assertContains('tool.my-tool', $disabledFeatures);
    }

    public function testDisablesResource(): void
    {
        mateDisableFeature('vendor/package', 'resource', 'my-resource');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertCount(1, $disabledFeatures);
        $this->assertContains('resource.my-resource', $disabledFeatures);
    }

    public function testDisablesPrompt(): void
    {
        mateDisableFeature('vendor/package', 'prompt', 'my-prompt');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertCount(1, $disabledFeatures);
        $this->assertContains('prompt.my-prompt', $disabledFeatures);
    }

    public function testDisablesMultipleFeaturesForSameExtension(): void
    {
        mateDisableFeature('vendor/package', 'tool', 'tool1');
        mateDisableFeature('vendor/package', 'tool', 'tool2');
        mateDisableFeature('vendor/package', 'resource', 'resource1');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertCount(3, $disabledFeatures);
        $this->assertContains('tool.tool1', $disabledFeatures);
        $this->assertContains('tool.tool2', $disabledFeatures);
        $this->assertContains('resource.resource1', $disabledFeatures);
    }

    public function testDisablesFeaturesForDifferentExtensions(): void
    {
        mateDisableFeature('vendor/package-a', 'tool', 'tool1');
        mateDisableFeature('vendor/package-b', 'tool', 'tool2');

        $disabledFeaturesA = mateGetDisabledFeatures('vendor/package-a');
        $disabledFeaturesB = mateGetDisabledFeatures('vendor/package-b');

        $this->assertCount(1, $disabledFeaturesA);
        $this->assertContains('tool.tool1', $disabledFeaturesA);

        $this->assertCount(1, $disabledFeaturesB);
        $this->assertContains('tool.tool2', $disabledFeaturesB);
    }

    public function testReturnsEmptyArrayForExtensionWithNoDisabledFeatures(): void
    {
        $disabledFeatures = mateGetDisabledFeatures('vendor/unknown');

        $this->assertCount(0, $disabledFeatures);
    }

    public function testThrowsExceptionForInvalidFeatureType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid feature type');

        mateDisableFeature('vendor/package', 'invalid', 'feature-name');
    }

    public function testAcceptsResourceTemplateType(): void
    {
        mateDisableFeature('vendor/package', 'resourceTemplate', 'my-template');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertCount(1, $disabledFeatures);
        $this->assertContains('resourceTemplate.my-template', $disabledFeatures);
    }

    public function testAcceptsFeatureNamesWithHyphens(): void
    {
        mateDisableFeature('vendor/package', 'tool', 'my-tool-name');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertContains('tool.my-tool-name', $disabledFeatures);
    }

    public function testAcceptsFeatureNamesWithUnderscores(): void
    {
        mateDisableFeature('vendor/package', 'tool', 'my_tool_name');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertContains('tool.my_tool_name', $disabledFeatures);
    }

    public function testAcceptsFeatureNamesWithNumbers(): void
    {
        mateDisableFeature('vendor/package', 'tool', 'tool123');

        $disabledFeatures = mateGetDisabledFeatures('vendor/package');

        $this->assertContains('tool.tool123', $disabledFeatures);
    }
}
