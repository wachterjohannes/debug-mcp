<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Mate\Bridge\Monolog;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $configurator->parameters()
        ->set('ai_mate_monolog.log_dir', '%root_dir%/var/log');

    $configurator->services()
        ->set(Monolog\Service\LogParser::class)

        ->set(Monolog\Service\LogReader::class)
            ->arg('$logDir', '%ai_mate_monolog.log_dir%')

        ->set(Monolog\Capability\LogSearchTool::class);
};
