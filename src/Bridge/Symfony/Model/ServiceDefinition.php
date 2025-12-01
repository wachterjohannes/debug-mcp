<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Mate\Bridge\Symfony\Model;

/**
 * @internal
 */
class ServiceDefinition
{
    public function __construct(
        public string $id,
        /**
         * @var ?class-string
         */
        public ?string $class,
        /**
         * If this has a value, it is the "real" definition's id.
         */
        public ?string $alias,
        /**
         * @var array<string>
         */
        public array $calls,
        /**
         * @var list<ServiceTag>
         */
        public array $tags,
        /**
         * @var array{0: string|null, 1: string}
         */
        public array $constructor,
    ) {
    }
}
