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

/**
 * Add some config in the project root, automatically discover tools.
 * Basically do every thing you need to set things up.
 */
class InitCommand extends Command
{
    public function __construct(
        private Configuration $config,
    ) {
        parent::__construct(self::getDefaultName());
    }

    public static function getDefaultName(): ?string
    {
        return 'init';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $root = $this->config->rootDir;
        $mateDir = $root.'/.mate';
        $filePath = $mateDir.'/extensions.php';

        if (!is_dir($mateDir)) {
            mkdir($mateDir, 0755, true);
        }

        if (file_exists($filePath)) {
            if ($io->confirm('File already exists. Overwrite? (y/n)', false)) {
                unlink($filePath);
                $this->addConfigFile($io, $filePath);
            }
        } else {
            $this->addConfigFile($io, $filePath);
        }

        $io->note('Please run "vendor/bin/mate discover" to find MCP features in your vendors folder');

        return Command::SUCCESS;
    }

    private function addConfigFile(SymfonyStyle $io, string $filePath): void
    {
        copy(__DIR__.'/../../resources/extensions.php', $filePath);
        $io->success(\sprintf('Wrote config file to "%s"', $filePath));
    }
}
