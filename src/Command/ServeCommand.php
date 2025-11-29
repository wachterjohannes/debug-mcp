<?php

namespace Wachterjohannes\DebugMcp\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wachterjohannes\DebugMcp\Server;

/**
 * Start the MCP server
 */
class ServeCommand extends Command
{
    public static function getDefaultName(): ?string
    {
        return 'serve';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $server = new Server();
        $server->run();

        return Command::SUCCESS;
    }

}