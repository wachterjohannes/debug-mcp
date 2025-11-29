<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Command;

use Mcp\Capability\Registry\Container;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StdioTransport;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wachterjohannes\DebugMcp\Model\Configuration;

/**
 * Start the MCP server
 */
class ServeCommand extends Command
{
    public function __construct(
        private LoggerInterface $logger,
        private Configuration $config,
    ) {
        parent::__construct();
    }

    public static function getDefaultName(): ?string
    {
        return 'serve';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = new Container();
        $container->set(LoggerInterface::class, $this->logger);
        $cacheDir = $this->config->get('cacheDir');
        $rootDir = $this->config->get('rootDir');

        $scanDirs = array_filter(array_map(function ($item) use ($rootDir) {
            if (! is_dir($rootDir.'/vendor/'.$item)) {
                $this->logger->error('Plugin "'.$item.'" not found');
                return null;
            }

            return 'vendor/'.$item;

        }, $this->config->get('enabled_plugins')));
        $scanDirs[] = substr(dirname(__DIR__, 2) . '/mcp', strlen($rootDir));

        $server = Server::builder()
            ->setServerInfo('debug-mcp', '0.1.1', 'Extensible MCP server for PHP development')
            ->setContainer($container)
            ->setDiscovery(basePath: $rootDir, scanDirs: $scanDirs)
            ->setSession(new FileSessionStore($cacheDir.'/sessions'))
            ->setLogger($this->logger)
            ->build();

        // Start listening (blocks until stdin EOF)
        $server->run(new StdioTransport());

        return Command::SUCCESS;
    }

}
