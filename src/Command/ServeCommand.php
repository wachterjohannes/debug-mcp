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
use Symfony\AI\Mate\Model\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Start the MCP server.
 */
class ServeCommand extends Command
{
    public function __construct(
        private LoggerInterface $logger,
        private Configuration $config,
    ) {
        parent::__construct(self::getDefaultName());
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

        $server = Server::builder()
            ->setServerInfo('ai-mate', '0.1.0', 'Symfony AI development assistant MCP server')
            ->setContainer($container)
            ->setDiscovery(basePath: $this->config->get('rootDir'), scanDirs: $this->getDirectoriesToScan())
            ->setSession(new FileSessionStore($cacheDir.'/sessions'))
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
        $rootDir = $this->config->get('rootDir');
        $scanDirs = array_filter(array_map(function ($item) use ($rootDir) {
            if (!is_dir($rootDir.'/vendor/'.$item)) {
                $this->logger->error('Plugin "'.$item.'" not found');

                return null;
            }

            return 'vendor/'.$item;
        }, $this->config->get('enabled_plugins') ?? []));

        foreach ($this->config->get('scanDir') ?? [] as $dir) {
            $dir = trim($dir);
            if (\is_string($dir) && '' !== $dir) {
                $scanDirs[] = $dir;
            }
        }

        // '/mcp' here refers to this project's MCP features
        $scanDirs[] = substr(\dirname(__DIR__, 2).'/mcp', \strlen($rootDir));

        return $scanDirs;
    }
}
