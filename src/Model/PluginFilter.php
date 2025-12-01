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
     * @param string[] $exclude
     * @param string[] $includeOnly
     */
    private function __construct(
        public readonly array $exclude = [],
        public readonly array $includeOnly = [],
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
     * Create filter that excludes specific classes.
     *
     * @param string|string[] $classes
     */
    public static function exclude(string|array $classes): self
    {
        return new self(exclude: (array) $classes);
    }

    /**
     * Create filter that only includes specific classes.
     *
     * @param string|string[] $classes
     */
    public static function includeOnly(string|array $classes): self
    {
        return new self(includeOnly: (array) $classes);
    }

    public function allows(string $className): bool
    {
        // If include_only is set, only allow those classes
        if ([] !== $this->includeOnly) {
            return \in_array($className, $this->includeOnly, true);
        }

        // If exclude is set, reject excluded classes
        if ([] !== $this->exclude) {
            return !\in_array($className, $this->exclude, true);
        }

        // No filters, allow everything
        return true;
    }

    public function hasFilters(): bool
    {
        return [] !== $this->exclude || [] !== $this->includeOnly;
    }
}
