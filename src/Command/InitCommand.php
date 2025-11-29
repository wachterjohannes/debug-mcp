<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wachterjohannes\DebugMcp\Model\Configuration;

/**
 * Add some config in the project root, automatically discover tools.
 * Basically do every thing you need to set things up.
 */
class InitCommand extends Command
{
    public function __construct(
        private Configuration $config,
    ) {
        parent::__construct();
    }


    public static function getDefaultName(): ?string
    {
        return 'init';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $root = $this->config->get('rootDir');
        $filePath = $root . '/.mcp.php';
        if (file_exists($filePath)) {
            if ($io->confirm('File already exists. Overwrite? (y/n)', false)) {
                unlink($filePath);
                $this->addConfigFile($io, $filePath);
            }
        } else {
            $this->addConfigFile($io, $filePath);
        }

        $io->note('Please run "vendor/bin/debug-mcp discover" to find MCP features in your vendors folder');

        return Command::SUCCESS;
    }

    private function addConfigFile(SymfonyStyle $io, string $filePath): void
    {
        copy(__DIR__ . '/../../resources/.mcp.php', $filePath);
        $io->success(sprintf('Wrote config file to "%s"', $filePath));
    }
}
