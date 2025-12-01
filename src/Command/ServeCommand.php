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

use Mcp\Capability\Registry\Container;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StdioTransport;
use Psr\Log\LoggerInterface;
use Symfony\AI\Mate\Discovery\ComposerTypeDiscovery;
use Symfony\AI\Mate\Model\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Start the MCP server.
 */
class ServeCommand extends Command
{
    private ComposerTypeDiscovery $discovery;

    public function __construct(
        private LoggerInterface $logger,
        private Configuration $config,
    ) {
        parent::__construct(self::getDefaultName());
        $this->discovery = new ComposerTypeDiscovery($config->rootDir, $logger);
    }

    public static function getDefaultName(): ?string
    {
        return 'serve';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = new Container();
        $container->set(LoggerInterface::class, $this->logger);

        $server = Server::builder()
            ->setServerInfo('ai-mate', '0.1.0', 'Symfony AI development assistant MCP server')
            ->setContainer($container)
            ->setDiscovery(basePath: $this->config->rootDir, scanDirs: $this->getDirectoriesToScan())
            ->setSession(new FileSessionStore($this->config->cacheDir.'/sessions'))
            ->setLogger($this->logger)
            ->build();

        // Start listening (blocks until stdin EOF)
        $server->run(new StdioTransport());

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function getDirectoriesToScan(): array
    {
        $scanDirs = [];

        // 1. Discover Composer-based extensions (with whitelist and filters)
        $extensions = $this->discovery->discover($this->config->enabledPlugins);
        foreach ($extensions as $packageName => $data) {
            foreach ($data['dirs'] as $dir) {
                $scanDirs[] = $dir;
            }

            // TODO: Apply filters from $data['filter'] during capability discovery
            // This requires integration with MCP SDK's Container/Registry system
            // See: https://github.com/wachterjohannes/debug-mcp/issues/7
            if ($data['filter']->hasFilters()) {
                $this->logger->debug('Plugin has filters configured', [
                    'package' => $packageName,
                    'exclude' => $data['filter']->exclude,
                    'include_only' => $data['filter']->includeOnly,
                ]);
            }
        }

        // 2. Add custom scan directories from configuration
        foreach ($this->config->scanDirs as $dir) {
            $dir = trim($dir);
            if ('' !== $dir) {
                $scanDirs[] = $dir;
            }
        }

        // 3. Always include local mcp/ directory (trusted project code)
        $scanDirs[] = substr(\dirname(__DIR__, 2).'/mcp', \strlen($this->config->rootDir));

        return $scanDirs;
    }
}
