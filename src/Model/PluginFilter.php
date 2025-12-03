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

final class PluginFilter
{
    /**
     * @param string[] $excludeFeatures Feature names to exclude (e.g., 'tool.analyze', 'resource.config')
     */
    private function __construct(
        public readonly array $excludeFeatures = [],
    ) {
    }

    /**
     * Create filter with no restrictions (include all).
     */
    public static function all(): self
    {
        return new self();
    }

    /**
     * Check if a class is allowed.
     *
     * Note: Class-level filtering has been removed. This method now always returns true.
     * It's kept for backward compatibility and will be removed in a future version.
     *
     * @deprecated Class-level filtering is no longer supported. Use feature-level filtering instead.
     */
    public function allows(string $className): bool
    {
        // Class-level filtering removed - all classes are allowed
        return true;
    }

    /**
     * Check if a specific feature is allowed.
     *
     * @param string $type Feature type (tool, resource, prompt)
     * @param string $name Feature name
     */
    public function allowsFeature(string $type, string $name): bool
    {
        if ([] === $this->excludeFeatures) {
            return true;
        }

        $featureId = $type.'.'.$name;

        return !\in_array($featureId, $this->excludeFeatures, true);
    }

    /**
     * Merge this filter with feature exclusions from the global registry.
     *
     * @param string $extension Extension identifier
     */
    public function withDisabledFeatures(string $extension): self
    {
        $disabledFeatures = mcpGetDisabledFeatures($extension);
        if ([] === $disabledFeatures) {
            return $this;
        }

        return new self(
            array_merge($this->excludeFeatures, $disabledFeatures),
        );
    }
}
