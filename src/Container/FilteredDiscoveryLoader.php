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
use Symfony\AI\Mate\Model\ExtensionFilter;
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
     * @param array<string, array{dirs: string[], filter: ExtensionFilter, includes: string[]}> $extensions
     */
    public function __construct(
        private string $basePath,
        private array $extensions,
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
        foreach ($this->extensions as $data) {
            $discoveryState = $this->discoverCapabilities($data['dirs']);

            foreach ($discoveryState->getTools() as $tool) {
                $this->maybeRegisterHandler($tool->handler);
            }

            foreach ($discoveryState->getResources() as $resource) {
                $this->maybeRegisterHandler($resource->handler);
            }

            foreach ($discoveryState->getPrompts() as $prompt) {
                $this->maybeRegisterHandler($prompt->handler);
            }

            foreach ($discoveryState->getResourceTemplates() as $template) {
                $this->maybeRegisterHandler($template->handler);
            }
        }
    }

    public function load(RegistryInterface $registry): void
    {
        $allTools = [];
        $allResources = [];
        $allPrompts = [];
        $allResourceTemplates = [];

        foreach ($this->extensions as $packageName => $data) {
            /** @var ExtensionFilter $filter */
            $filter = $data['filter']->withDisabledFeatures($packageName);

            $discoveryState = $this->discoverCapabilities($data['dirs']);

            foreach ($discoveryState->getTools() as $name => $tool) {
                if (!$filter->allowsFeature('tool', $name)) {
                    $this->logger->debug('Excluding tool by feature filter', [
                        'package' => $packageName,
                        'tool' => $name,
                    ]);
                    continue;
                }

                $allTools[$name] = $tool;
            }

            foreach ($discoveryState->getResources() as $uri => $resource) {
                if (!$filter->allowsFeature('resource', $uri)) {
                    $this->logger->debug('Excluding resource by feature filter', [
                        'package' => $packageName,
                        'resource' => $uri,
                    ]);
                    continue;
                }

                $allResources[$uri] = $resource;
            }

            foreach ($discoveryState->getPrompts() as $name => $prompt) {
                if (!$filter->allowsFeature('prompt', $name)) {
                    $this->logger->debug('Excluding prompt by feature filter', [
                        'package' => $packageName,
                        'prompt' => $name,
                    ]);
                    continue;
                }

                $allPrompts[$name] = $prompt;
            }

            // Filter and collect resource templates
            foreach ($discoveryState->getResourceTemplates() as $uriTemplate => $template) {
                // Check if a feature is disabled
                if (!$filter->allowsFeature('resourceTemplate', $uriTemplate)) {
                    $this->logger->debug('Excluding resource template by feature filter', [
                        'package' => $packageName,
                        'template' => $uriTemplate,
                    ]);
                    continue;
                }

                $allResourceTemplates[$uriTemplate] = $template;
            }
        }

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
    private function maybeRegisterHandler(\Closure|array|string $handler): void
    {
        $className = $this->extractClassName($handler);
        if (null === $className) {
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

        return $discoverer->discover($this->basePath, $scanDirs);
    }

    /**
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

        $class = $handler[0];
        if (\is_object($class)) {
            return $class::class;
        }

        return class_exists($class) ? $class : null;
    }

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
