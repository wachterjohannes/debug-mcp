<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Mate\Bridge\Symfony;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $configurator->parameters()
        ->set('ai_mate_symfony.cache_dir', '%root_dir%/cache');

    $configurator->services()
        ->set(Symfony\Capability\ServiceTool::class)
            ->arg('$cacheDir', '%ai_mate_symfony.cache_dir%')
        ->set(Symfony\Service\ContainerProvider::class);
};
