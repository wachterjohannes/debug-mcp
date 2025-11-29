<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Wachterjohannes\DebugMcp\Command\DiscoverCommand;
use Wachterjohannes\DebugMcp\Command\InitCommand;
use Wachterjohannes\DebugMcp\Command\ServeCommand;
use Wachterjohannes\DebugMcp\Model\Configuration;
use Wachterjohannes\DebugMcp\Service\Logger;

class App
{
    public static function build(Configuration $config): Application
    {
        $logger = new Logger();
        $application = new Application('Debug MCP', '1.0.0');

        self::addCommand($application, new InitCommand($config));
        self::addCommand($application, new ServeCommand($logger, $config));
        self::addCommand($application, new DiscoverCommand());

        return $application;
    }

    /**
     * Add commands in a way that works with all support symfony/console versions
     */
    private static function addCommand(Application $application, Command $command)
    {
        if (method_exists($application, 'addCommand')) {
            $application->addCommand($command);
        } elseif (method_exists($application, 'add')) {
            $application->add($command);
        } else {
            throw new \RuntimeException('Unsupported version of symfony/console. We cannot add commands');
        }
    }
}
