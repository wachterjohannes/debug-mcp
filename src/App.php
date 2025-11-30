<?php

namespace Symfony\AI\Mate;

use Symfony\AI\Mate\Command\DiscoverCommand;
use Symfony\AI\Mate\Command\InitCommand;
use Symfony\AI\Mate\Command\ServeCommand;
use Symfony\AI\Mate\Model\Configuration;
use Symfony\AI\Mate\Service\Logger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class App
{
    public static function build(Configuration $config): Application
    {
        $logger = new Logger();
        $application = new Application('Symfony AI Mate', '0.1.0');

        self::addCommand($application, new InitCommand($config));
        self::addCommand($application, new ServeCommand($logger, $config));
        self::addCommand($application, new DiscoverCommand($config));

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
