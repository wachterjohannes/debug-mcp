<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Mate\Command;

use Mcp\Capability\Discovery\Discoverer;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StdioTransport;
use Psr\Log\LoggerInterface;
use Symfony\AI\Mate\Discovery\ComposerTypeDiscovery;
use Symfony\AI\Mate\Discovery\FilteredDiscoveryLoader;
use Symfony\AI\Mate\Discovery\ServiceDiscovery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Start the MCP server.
 */
class ServeCommand extends Command
{
    private ComposerTypeDiscovery $discovery;

    public function __construct(
        private LoggerInterface $logger,
        private ContainerBuilder $container,
    ) {
        parent::__construct(self::getDefaultName());
        $rootDir = $container->getParameter('mate.root_dir');
        \assert(\is_string($rootDir));
        $this->discovery = new ComposerTypeDiscovery($rootDir, $logger);
    }

    public static function getDefaultName(): ?string
    {
        return 'serve';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootDir = $this->container->getParameter('mate.root_dir');
        \assert(\is_string($rootDir));

        $cacheDir = $this->container->getParameter('mate.cache_dir');
        \assert(\is_string($cacheDir));


        // Pre-register discovered services in the container (before compilation)
        $discovery = new Discoverer($this->logger);
        $extensions = $this->getExtensionsToLoad();
        (new ServiceDiscovery())->registerServices($discovery, $this->container, $rootDir, $extensions);

        $disabledVendorFeatures = $this->container->getParameter('mate.disabled_features') ?? [];

        // Compile the container (resolves parameters and validates)
        $this->container->compile();

        // Create loader to discover services from vendors
        $loader = new FilteredDiscoveryLoader(
            basePath: $rootDir,
            extensions: $extensions,
            disabledFeatures: $disabledVendorFeatures,
            discoverer: $discovery,
            logger: $this->logger
        );

        // Build and run MCP server
        $server = Server::builder()
            ->setServerInfo('ai-mate', '0.1.0', 'Symfony AI development assistant MCP server')
            ->setContainer($this->container)
            ->addLoaders($loader)
            ->setDiscovery(\dirname(__DIR__).'/Capability')
            ->setSession(new FileSessionStore($cacheDir.'/sessions'))
            ->setLogger($this->logger)
            ->build();

        // Start listening (blocks until stdin EOF)
        $server->run(new StdioTransport());

        return Command::SUCCESS;
    }

    /**
     * Get all extensions to load with their scan directories and filters.
     *
     * @return array<string, array{dirs: string[], includes: string[]}>
     */
    private function getExtensionsToLoad(): array
    {
        $rootDir = $this->container->getParameter('mate.root_dir');
        \assert(\is_string($rootDir));

        $packageNames = $this->container->getParameter('mate.enabled_extensions');
        \assert(\is_array($packageNames));
        /** @var array<int, string> $packageNames */

        $scanDirs = $this->container->getParameter('mate.scan_dirs');
        \assert(\is_array($scanDirs));

        $extensions = [];

        // Discover enabled Composer-based extensions
        foreach ($this->discovery->discover($packageNames) as $packageName => $data) {
            $extensions[$packageName] = $data;
        }

        // Add custom scan directories from the configuration
        $customDirs = [];
        foreach ($scanDirs as $dir) {
            if (\is_string($dir)) {
                $dir = trim($dir);
                if ('' !== $dir) {
                    // TODO make sure it is inside the package.
                    $customDirs[] = $dir;
                }
            }
        }
        if ([] !== $customDirs) {
            $extensions['_custom'] = [
                'dirs' => $customDirs,
                'includes' => [],
            ];
        }

        return $extensions;
    }
}
