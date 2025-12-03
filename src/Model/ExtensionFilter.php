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

final class ExtensionFilter
{
    /**
     * @param string[] $excludeFeatures
     */
    private function __construct(
        public readonly array $excludeFeatures = [],
    ) {
    }

    public static function all(): self
    {
        return new self();
    }

    public function allowsFeature(string $type, string $name): bool
    {
        if ([] === $this->excludeFeatures) {
            return true;
        }

        $featureId = $type.'.'.$name;

        return !\in_array($featureId, $this->excludeFeatures, true);
    }

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
