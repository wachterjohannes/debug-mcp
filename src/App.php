<?php

namespace Wachterjohannes\DebugMcp;

use Symfony\Component\Console\Application;
use Wachterjohannes\DebugMcp\Command\AutoDiscoverCommand;
use Wachterjohannes\DebugMcp\Command\InitCommand;
use Wachterjohannes\DebugMcp\Command\ServeCommand;

class App
{
    public static function build(array $config): Application
    {
        $application = new Application('Debug MCP', '1.0.0');

        $application->addCommand(new InitCommand());
        $application->addCommand(new ServeCommand());
        $application->addCommand(new AutoDiscoverCommand());

        return $application;
    }
}