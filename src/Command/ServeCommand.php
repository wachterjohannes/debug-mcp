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

use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StdioTransport;
use Psr\Log\LoggerInterface;
use Symfony\AI\Mate\Container\ContainerFactory;
use Symfony\AI\Mate\Container\FilteredDiscoveryLoader;
use Symfony\AI\Mate\Discovery\ComposerTypeDiscovery;
use Symfony\AI\Mate\Model\PluginFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;

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
        $this->discovery = new ComposerTypeDiscovery($rootDir, $logger);
    }

    public static function getDefaultName(): ?string
    {
        return 'serve';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 0. Discover extensions with their filters and service files
        $extensions = $this->getExtensionsToLoad();

        // 1. Load environment variables from .env files
        if (null !== $this->container->getParameter('mate.env_file')) {
            $extra = [];
            $localFile = $this->container->getParameter('mate.root_dir').\DIRECTORY_SEPARATOR.$this->container->getParameter('mate.env_file').\DIRECTORY_SEPARATOR.'.local';
            if (!file_exists($localFile)) {
                $extra[] = $localFile;
            }
            (new Dotenv())->load($this->container->getParameter('mate.root_dir').\DIRECTORY_SEPARATOR.$this->container->getParameter('mate.env_file'), ...$extra);
        }

        // 2. Build Symfony DI container with extension services
        $containerFactory = new ContainerFactory($this->logger);
        $container = $containerFactory->create($extensions);

        // 3. Create filtered discovery loader
        $loader = new FilteredDiscoveryLoader(
            basePath: $this->container->getParameter('mate.root_dir'),
            extensions: $extensions,
            excludeDirs: [],
            logger: $this->logger,
            container: $container,
        );

        // 4. Pre-register discovered services in the container (before compilation)
        $loader->registerServices();

        // 5. Compile the container (resolves parameters and validates)
        $container->compile();

        // 6. Build and run MCP server
        $server = Server::builder()
            ->setServerInfo('ai-mate', '0.1.0', 'Symfony AI development assistant MCP server')
            ->setContainer($container)
            ->addLoaders($loader)
            ->setDiscovery(\dirname(__DIR__).'/Capability')
            ->setSession(new FileSessionStore($this->container->getParameter('mate.cache_dir').'/sessions'))
            ->setLogger($this->logger)
            ->build();

        // Start listening (blocks until stdin EOF)
        $server->run(new StdioTransport());

        return Command::SUCCESS;
    }

    /**
     * Get all extensions to load with their scan directories and filters.
     *
     * @return array<string, array{dirs: string[], filter: PluginFilter, includes: string[]}>
     */
    private function getExtensionsToLoad(): array
    {
        $extensions = [];

        // 1. Discover Composer-based extensions (with whitelist and filters)
        foreach ($this->discovery->discover($this->container->getParameter('mate.enabled_plugins')) as $packageName => $data) {
            $extensions[$packageName] = $data;
        }

        // 2. Add custom scan directories from configuration
        $customDirs = [];
        foreach ($this->container->getParameter('mate.scan_dirs') as $dir) {
            $dir = trim($dir);
            if ('' !== $dir) {
                // TODO make sure it is inside the package.
                $customDirs[] = $dir;
            }
        }
        if ([] !== $customDirs) {
            $extensions['_custom'] = [
                'dirs' => $customDirs,
                'filter' => PluginFilter::all(),
                'includes' => [],
            ];
        }

        // 3. Always include local mate/ directory (trusted project code)
        $mateDir = substr(\dirname(__DIR__, 2).'/mate', \strlen($this->container->getParameter('mate.root_dir')));
        $extensions['_local'] = [
            'dirs' => [$mateDir],
            'filter' => PluginFilter::all(),
            'includes' => [],
        ];

        return $extensions;
    }
}
