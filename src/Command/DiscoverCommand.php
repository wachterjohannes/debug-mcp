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

use Psr\Log\LoggerInterface;
use Symfony\AI\Mate\Discovery\ComposerTypeDiscovery;
use Symfony\AI\Mate\Model\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Discover MCP extensions installed via Composer.
 *
 * Scans for packages with type "ai-mate-extension"
 * and suggests adding them to the enabled_plugins configuration.
 */
class DiscoverCommand extends Command
{
    public function __construct(
        private Configuration $config,
        private LoggerInterface $logger,
    ) {
        parent::__construct(self::getDefaultName());
    }

    public static function getDefaultName(): ?string
    {
        return 'discover';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $discovery = new ComposerTypeDiscovery($this->config->rootDir, $this->logger);

        // Discover all extensions, regardless of whitelist
        $extensions = $discovery->discover([]);

        $count = \count($extensions);
        if (0 === $count) {
            $io->warning('No MCP extensions found. Packages must have type "ai-mate-extension" in composer.json.');

            return Command::SUCCESS;
        }

        $io->success(\sprintf('Discovered %d MCP extension%s.', $count, 1 === $count ? '' : 's'));
        $io->writeln('');

        $io->section('Discovered Extensions');
        foreach ($extensions as $packageName => $scanDirs) {
            $io->writeln(\sprintf('  • <info>%s</info>', $packageName));
            foreach ($scanDirs as $dir) {
                $io->writeln(\sprintf('    └─ %s', $dir));
            }
        }

        $io->writeln('');
        $io->section('Configuration');
        $io->writeln('Add these packages to your .mcp.php config file:');
        $io->writeln('');

        $content = implode("',\n        '", array_keys($extensions));

        $io->writeln('// .mcp.php');
        $io->writeln(<<<PHP
return [
    // ...
    'enabled_plugins' => [
        '$content',
    ],
];

PHP);

        return Command::SUCCESS;
    }
}
