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

use Symfony\AI\Mate\Model\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Look at the vendor directory and ask if we should add some
 * MCP tools/features etc to our config.
 */
class DiscoverCommand extends Command
{
    public function __construct(
        private Configuration $config,
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

        $root = $this->config->get('rootDir');
        $finder = (new Finder())
            ->in($root.'/vendor')
            ->name('*.php')
            ->exclude(['composer', 'bin', 'mcp/sdk']);

        $packages = [];
        foreach ($finder as $file) {
            $this->processFile($file, $packages);
        }

        $count = \count($packages);
        if (0 === $count) {
            $io->warning('No packages found with MCP features.');

            return Command::SUCCESS;
        }
        $io->success('Discovered '.$count.' packages with MCP features. Please add them to your .mcp.php config file.');

        $content = implode("',\n        '", array_keys($packages));

        $io->writeln('// .mcp.php');
        $io->writeln('');
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

    private function processFile(SplFileInfo $file, array &$packages): void
    {
        $content = file_get_contents($file->getPathname());

        // TODO make this better and more dynamic
        if (str_contains($content, 'Mcp\Capability\Attribute')) {
            $parts = explode(\DIRECTORY_SEPARATOR, $file->getRelativePath());

            $package = $parts[0].'/'.$parts[1];
            if (!isset($packages[$package])) {
                $packages[$package] = [];
            }
            $packages[$package][] = $file->getRelativePathname();
        }
    }
}
