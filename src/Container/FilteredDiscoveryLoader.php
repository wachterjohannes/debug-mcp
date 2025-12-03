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

use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\Mate\Model\PluginFilter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Discovery loader that filters capabilities based on plugin configuration
 * and registers discovered classes in the Symfony DI container.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
final class FilteredDiscoveryLoader implements LoaderInterface
{
    /**
     * @param array<string, array{dirs: string[], filter: PluginFilter, includes: string[]}> $extensions
     * @param string[]                                                                       $excludeDirs
     */
    public function __construct(
        private string $basePath,
        private array $extensions,
        private array $excludeDirs,
        private LoggerInterface $logger,
        private ContainerBuilder $container,
    ) {
    }

    /**
     * Pre-register all discovered services in the container.
     * Call this BEFORE container->compile().
     */
    public function registerServices(): void
    {
        /** @var array{dirs: string[], filter: PluginFilter} $data */
        foreach ($this->extensions as $packageName => $data) {
            $scanDirs = $data['dirs'];
            $filter = $data['filter'];

            $discoveryState = $this->discoverCapabilities($scanDirs);

            foreach ($discoveryState->getTools() as $tool) {
                $this->maybeRegisterHandler($tool->handler, $filter, $packageName);
            }

            foreach ($discoveryState->getResources() as $resource) {
                $this->maybeRegisterHandler($resource->handler, $filter, $packageName);
            }

            foreach ($discoveryState->getPrompts() as $prompt) {
                $this->maybeRegisterHandler($prompt->handler, $filter, $packageName);
            }

            foreach ($discoveryState->getResourceTemplates() as $template) {
                $this->maybeRegisterHandler($template->handler, $filter, $packageName);
            }
        }
    }

    public function load(RegistryInterface $registry): void
    {
        $allTools = [];
        $allResources = [];
        $allPrompts = [];
        $allResourceTemplates = [];

        /** @var array{dirs: string[], filter: PluginFilter} $data */
        foreach ($this->extensions as $packageName => $data) {
            $scanDirs = $data['dirs'];
            $filter = $data['filter']->withDisabledFeatures($packageName);

            $discoveryState = $this->discoverCapabilities($scanDirs);

            // Filter and collect tools
            foreach ($discoveryState->getTools() as $name => $tool) {
                // Check if feature is disabled
                if (!$filter->allowsFeature('tool', $name)) {
                    $this->logger->debug('Excluding tool by feature filter', [
                        'package' => $packageName,
                        'tool' => $name,
                    ]);
                    continue;
                }

                // Check if class is disabled
                $className = $this->extractClassName($tool->handler);
                if (null !== $className && !$filter->allows($className)) {
                    $this->logger->debug('Excluding tool by class filter', [
                        'package' => $packageName,
                        'tool' => $name,
                        'class' => $className,
                    ]);
                    continue;
                }

                $allTools[$name] = $tool;
            }

            // Filter and collect resources
            foreach ($discoveryState->getResources() as $uri => $resource) {
                // Check if feature is disabled
                if (!$filter->allowsFeature('resource', $uri)) {
                    $this->logger->debug('Excluding resource by feature filter', [
                        'package' => $packageName,
                        'resource' => $uri,
                    ]);
                    continue;
                }

                // Check if class is disabled
                $className = $this->extractClassName($resource->handler);
                if (null !== $className && !$filter->allows($className)) {
                    $this->logger->debug('Excluding resource by class filter', [
                        'package' => $packageName,
                        'resource' => $uri,
                        'class' => $className,
                    ]);
                    continue;
                }

                $allResources[$uri] = $resource;
            }

            // Filter and collect prompts
            foreach ($discoveryState->getPrompts() as $name => $prompt) {
                // Check if feature is disabled
                if (!$filter->allowsFeature('prompt', $name)) {
                    $this->logger->debug('Excluding prompt by feature filter', [
                        'package' => $packageName,
                        'prompt' => $name,
                    ]);
                    continue;
                }

                // Check if class is disabled
                $className = $this->extractClassName($prompt->handler);
                if (null !== $className && !$filter->allows($className)) {
                    $this->logger->debug('Excluding prompt by class filter', [
                        'package' => $packageName,
                        'prompt' => $name,
                        'class' => $className,
                    ]);
                    continue;
                }

                $allPrompts[$name] = $prompt;
            }

            // Filter and collect resource templates
            foreach ($discoveryState->getResourceTemplates() as $uriTemplate => $template) {
                // Note: Resource templates are not filterable by feature name,
                // only by class name since they use URI patterns not named features

                // Check if class is disabled
                $className = $this->extractClassName($template->handler);
                if (null !== $className && !$filter->allows($className)) {
                    $this->logger->debug('Excluding resource template by class filter', [
                        'package' => $packageName,
                        'template' => $uriTemplate,
                        'class' => $className,
                    ]);
                    continue;
                }

                $allResourceTemplates[$uriTemplate] = $template;
            }
        }

        // Create filtered discovery state and apply to registry
        $filteredState = new DiscoveryState(
            $allTools,
            $allResources,
            $allPrompts,
            $allResourceTemplates,
        );

        $registry->setDiscoveryState($filteredState);

        $this->logger->info('Loaded filtered capabilities', [
            'tools' => \count($allTools),
            'resources' => \count($allResources),
            'prompts' => \count($allPrompts),
            'resourceTemplates' => \count($allResourceTemplates),
        ]);
    }

    /**
     * @param \Closure|array{0: object|string, 1: string}|string $handler
     */
    private function maybeRegisterHandler(\Closure|array|string $handler, PluginFilter $filter, string $packageName): void
    {
        $className = $this->extractClassName($handler);
        if (null === $className) {
            return;
        }

        if (!$filter->allows($className)) {
            return;
        }

        $this->registerService($className);
    }

    /**
     * @param string[] $scanDirs
     */
    private function discoverCapabilities(array $scanDirs): DiscoveryState
    {
        $discoverer = new Discoverer($this->logger);

        return $discoverer->discover($this->basePath, $scanDirs, $this->excludeDirs);
    }

    /**
     * Extract class name from handler.
     *
     * @param \Closure|array{0: object|string, 1: string}|string $handler
     */
    private function extractClassName(\Closure|array|string $handler): ?string
    {
        if ($handler instanceof \Closure) {
            return null;
        }

        if (\is_string($handler)) {
            return class_exists($handler) ? $handler : null;
        }

        // Handler is array{0: object|string, 1: string}
        $class = $handler[0];
        if (\is_object($class)) {
            return $class::class;
        }

        // $class is string
        return class_exists($class) ? $class : null;
    }

    /**
     * Register a class as a service in the container if not already registered.
     */
    private function registerService(string $className): void
    {
        if ($this->container->has($className)) {
            return;
        }

        $this->container->register($className, $className)
            ->setAutowired(true)
            ->setPublic(true);
    }
}
