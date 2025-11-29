<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Model;

/**
 * TODO run validation some how.
 */
class Configuration
{
    public function __construct(
        private array $config
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }
}
