<?php

namespace Wachterjohannes\DebugMcp\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Add some config in the project root, automatically discover tools.
 * Basically do every thing you need to set things up.
 */
class InitCommand extends Command
{
    public static function getDefaultName(): ?string
    {
        return 'init';
    }
}