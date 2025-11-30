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

/**
 * TODO run validation some how.
 */
class Configuration
{
    public function __construct(
        private array $config,
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }
}
