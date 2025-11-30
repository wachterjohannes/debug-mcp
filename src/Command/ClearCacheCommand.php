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

/**
 * Clear the MCP server cache.
 */
class ClearCacheCommand extends Command
{
    public function __construct(
        private Configuration $config,
    ) {
        parent::__construct(self::getDefaultName());
    }

    public static function getDefaultName(): ?string
    {
        return 'clear-cache';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cacheDir = $this->config->cacheDir;

        if (!is_dir($cacheDir)) {
            $io->success('Cache directory does not exist. Nothing to clear.');

            return Command::SUCCESS;
        }

        $finder = new Finder();
        $finder->files()->in($cacheDir);

        $count = 0;
        foreach ($finder as $file) {
            unlink($file->getRealPath());
            ++$count;
        }

        if ($count > 0) {
            $io->success(\sprintf('Cleared %d cache file(s) from "%s"', $count, $cacheDir));
        } else {
            $io->success('Cache is already empty.');
        }

        return Command::SUCCESS;
    }
}
