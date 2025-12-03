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
use Symfony\AI\Mate\Discovery\ComposerTypeDiscovery;
use Symfony\AI\Mate\Model\ExtensionFilter;
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
        private string $rootDir,
    ) {
    }

    public function create(): ContainerBuilder
    {
        // 1. Build base container with default services
        $container = new ContainerBuilder();
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__)));
        $loader->load('default.services.php');

        // 2. Read enabled extensions from .mate/extensions.php
        $enabledPlugins = $this->getEnabledExtensions();

        // 3. Set base parameters
        $container->setParameter('mate.enabled_plugins', $enabledPlugins);
        $container->setParameter('mate.root_dir', $this->rootDir);

        // 4. Discover extensions and load their services
        if ([] !== $enabledPlugins) {
            $logger = $container->get(LoggerInterface::class);
            \assert($logger instanceof LoggerInterface);

            $discovery = new ComposerTypeDiscovery($this->rootDir, $logger);
            $extensions = $discovery->discover($enabledPlugins);

            if ([] !== $extensions) {
                $this->loadExtensionServices($container, $extensions);
            }
        }

        // 5. Load user services last (so they can override extension configs and access parameters)
        $this->loadUserServices($container);

        return $container;
    }

    /**
     * @return string[]
     */
    private function getEnabledExtensions(): array
    {
        $extensionsFile = $this->rootDir.'/.mate/extensions.php';

        if (!file_exists($extensionsFile)) {
            return [];
        }

        $extensionsConfig = include $extensionsFile;
        if (!\is_array($extensionsConfig)) {
            return [];
        }

        $enabledPlugins = [];
        foreach ($extensionsConfig as $packageName => $config) {
            if (\is_string($packageName) && \is_array($config) && ($config['enabled'] ?? false)) {
                $enabledPlugins[] = $packageName;
            }
        }

        return $enabledPlugins;
    }

    /**
     * @param array<string, array{dirs: string[], filter: ExtensionFilter, includes: string[]}> $extensions
     */
    private function loadExtensionServices(ContainerBuilder $container, array $extensions): void
    {
        $logger = $container->get(LoggerInterface::class);
        \assert($logger instanceof LoggerInterface);

        foreach ($extensions as $packageName => $data) {
            $this->loadExtensionIncludes($container, $logger, $packageName, $data['includes']);
        }
    }

    /**
     * @param string[] $includeFiles
     */
    private function loadExtensionIncludes(ContainerBuilder $container, LoggerInterface $logger, string $packageName, array $includeFiles): void
    {
        foreach ($includeFiles as $includeFile) {
            if (!file_exists($includeFile)) {
                continue;
            }

            try {
                $loader = new PhpFileLoader($container, new FileLocator(\dirname($includeFile)));
                $loader->load(basename($includeFile));

                $logger->debug('Loaded extension include', [
                    'package' => $packageName,
                    'file' => $includeFile,
                ]);
            } catch (\Throwable $e) {
                $logger->warning('Failed to load extension include', [
                    'package' => $packageName,
                    'file' => $includeFile,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function loadUserServices(ContainerBuilder $container): void
    {
        $userServicesFile = $this->rootDir.'/.mate/services.php';
        if (!file_exists($userServicesFile)) {
            return;
        }

        $logger = $container->get(LoggerInterface::class);
        \assert($logger instanceof LoggerInterface);

        try {
            $loader = new PhpFileLoader($container, new FileLocator($this->rootDir.'/.mate'));
            $loader->load('services.php');

            $logger->debug('Loaded user services', [
                'file' => $userServicesFile,
            ]);
        } catch (\Throwable $e) {
            $logger->warning('Failed to load user services', [
                'file' => $userServicesFile,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
