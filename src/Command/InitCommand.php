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
        private string $rootDir,
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

        $mateDir = $this->rootDir.'/.mate';

        if (!is_dir($mateDir)) {
            mkdir($mateDir, 0755, true);
        }

        // Create extensions.php
        $extensionsFile = $mateDir.'/extensions.php';
        if (file_exists($extensionsFile)) {
            if ($io->confirm('extensions.php already exists. Overwrite? (y/n)', false)) {
                unlink($extensionsFile);
                $this->copyTemplate('extensions.php', $extensionsFile);
                $io->success(\sprintf('Wrote %s', $extensionsFile));
            }
        } else {
            $this->copyTemplate('extensions.php', $extensionsFile);
            $io->success(\sprintf('Wrote %s', $extensionsFile));
        }

        // Create services.php
        $servicesFile = $mateDir.'/services.php';
        if (file_exists($servicesFile)) {
            if ($io->confirm('services.php already exists. Overwrite? (y/n)', false)) {
                unlink($servicesFile);
                $this->copyTemplate('services.php', $servicesFile);
                $io->success(\sprintf('Wrote %s', $servicesFile));
            }
        } else {
            $this->copyTemplate('services.php', $servicesFile);
            $io->success(\sprintf('Wrote %s', $servicesFile));
        }

        $io->note('Please run "vendor/bin/mate discover" to find MCP features in your vendors folder');

        return Command::SUCCESS;
    }

    private function copyTemplate(string $template, string $destination): void
    {
        copy(__DIR__.'/../../resources/'.$template, $destination);
    }
}
