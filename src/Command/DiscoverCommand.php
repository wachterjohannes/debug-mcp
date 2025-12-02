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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Discover MCP extensions installed via Composer.
 *
 * Scans for packages with extra.ai-mate configuration
 * and generates/updates .mate/extensions.php with discovered extensions.
 */
class DiscoverCommand extends Command
{
    public function __construct(
        private string $rootDir,
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

        $discovery = new ComposerTypeDiscovery($this->rootDir, $this->logger);

        // Discover all extensions, regardless of whitelist
        $extensions = $discovery->discover([]);

        $count = \count($extensions);
        if (0 === $count) {
            $io->warning('No MCP extensions found. Packages must have "extra.ai-mate" config in composer.json.');

            return Command::SUCCESS;
        }

        $io->success(\sprintf('Discovered %d MCP extension%s.', $count, 1 === $count ? '' : 's'));
        $io->writeln('');

        $io->section('Discovered Extensions');
        foreach ($extensions as $packageName => $data) {
            $io->writeln(\sprintf('  • <info>%s</info>', $packageName));
            foreach ($data['dirs'] as $dir) {
                $io->writeln(\sprintf('    └─ %s', $dir));
            }
        }

        // Load existing extensions.php if it exists
        $extensionsFile = $this->rootDir.'/.mate/extensions.php';
        $existingExtensions = [];
        if (file_exists($extensionsFile)) {
            $existingExtensions = include $extensionsFile;
            if (!\is_array($existingExtensions)) {
                $existingExtensions = [];
            }
        }

        // Merge discovered extensions with existing config
        $finalExtensions = [];
        foreach ($extensions as $packageName => $data) {
            // Preserve existing enabled state, default to true for new packages
            $finalExtensions[$packageName] = [
                'enabled' => $existingExtensions[$packageName]['enabled'] ?? true,
            ];
        }

        // Write to .mate/extensions.php
        $this->writeExtensionsFile($extensionsFile, $finalExtensions);

        $io->writeln('');
        $io->success(\sprintf('Updated %s', $extensionsFile));
        $io->note('Edit this file to enable/disable specific extensions.');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, array{enabled: bool}> $extensions
     */
    private function writeExtensionsFile(string $filePath, array $extensions): void
    {
        $dir = \dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "<?php\n\n";
        $content .= "// This file is managed by 'mate discover'\n";
        $content .= "// You can manually edit to enable/disable extensions\n\n";
        $content .= "return [\n";

        foreach ($extensions as $packageName => $config) {
            $enabled = $config['enabled'] ? 'true' : 'false';
            $content .= "    '$packageName' => ['enabled' => $enabled],\n";
        }

        $content .= "];\n";

        file_put_contents($filePath, $content);
    }
}
