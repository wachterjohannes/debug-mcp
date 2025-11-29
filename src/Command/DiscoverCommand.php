<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Look at the vendor directory and ask if we should add some
 * MCP tools/features etc to our config
 */
class DiscoverCommand extends Command
{
    public static function getDefaultName(): ?string
    {
        return 'discover';
    }
}
