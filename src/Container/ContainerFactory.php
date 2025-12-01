<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Mate\Container;

use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Factory for building a Symfony DI Container with MCP extension configurations.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
final class ContainerFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Create a ContainerBuilder with extension configurations loaded.
     *
     * @param array<string, array{dirs: string[], filter: \Symfony\AI\Mate\Model\PluginFilter, includes: string[]}> $extensions
     */
    public function create(array $extensions): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // Register core services
        $container->set(LoggerInterface::class, $this->logger);

        // Load extension include files
        foreach ($extensions as $packageName => $data) {
            $this->loadExtensionIncludes($container, $packageName, $data['includes']);
        }

        return $container;
    }

    /**
     * @param string[] $includeFiles
     */
    private function loadExtensionIncludes(ContainerBuilder $container, string $packageName, array $includeFiles): void
    {
        foreach ($includeFiles as $includeFile) {
            if (!file_exists($includeFile)) {
                continue;
            }

            try {
                $loader = new PhpFileLoader($container, new FileLocator(\dirname($includeFile)));
                $loader->load(basename($includeFile));

                $this->logger->debug('Loaded extension include', [
                    'package' => $packageName,
                    'file' => $includeFile,
                ]);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to load extension include', [
                    'package' => $packageName,
                    'file' => $includeFile,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
