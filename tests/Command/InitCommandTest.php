<?php

namespace Symfony\AI\Mate\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Mate\Command\InitCommand;
use Symfony\AI\Mate\Model\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/mate-test-'.uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testCreatesDirectoryAndConfigFile(): void
    {
        $config = $this->createConfiguration();
        $command = new InitCommand($config);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertDirectoryExists($this->tempDir.'/.mate');
        $this->assertFileExists($this->tempDir.'/.mate/extensions.php');

        $content = file_get_contents($this->tempDir.'/.mate/extensions.php');
        $this->assertIsString($content);
        $this->assertStringContainsString('enabled_plugins', $content);
    }

    public function testDisplaysSuccessMessage(): void
    {
        $config = $this->createConfiguration();
        $command = new InitCommand($config);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Wrote config file', $output);
        $this->assertStringContainsString('.mate/extensions.php', $output);
        $this->assertStringContainsString('vendor/bin/mate discover', $output);
    }

    public function testDoesNotOverwriteExistingFileWithoutConfirmation(): void
    {
        $config = $this->createConfiguration();
        $command = new InitCommand($config);
        $tester = new CommandTester($command);

        // Create existing file
        mkdir($this->tempDir.'/.mate', 0755, true);
        file_put_contents($this->tempDir.'/.mate/extensions.php', '<?php return ["test" => "value"];');

        // Execute with 'no' response
        $tester->setInputs(['no']);
        $tester->execute([]);

        // File should still contain original content
        $content = file_get_contents($this->tempDir.'/.mate/extensions.php');
        $this->assertIsString($content);
        $this->assertStringContainsString('test', $content);
        $this->assertStringContainsString('value', $content);
    }

    public function testOverwritesExistingFileWithConfirmation(): void
    {
        $config = $this->createConfiguration();
        $command = new InitCommand($config);
        $tester = new CommandTester($command);

        // Create existing file
        mkdir($this->tempDir.'/.mate', 0755, true);
        file_put_contents($this->tempDir.'/.mate/extensions.php', '<?php return ["test" => "value"];');

        // Execute with 'yes' response
        $tester->setInputs(['yes']);
        $tester->execute([]);

        // File should be overwritten with template content
        $content = file_get_contents($this->tempDir.'/.mate/extensions.php');
        $this->assertIsString($content);
        $this->assertStringNotContainsString('test', $content);
        $this->assertStringContainsString('enabled_plugins', $content);
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $config = $this->createConfiguration();
        $command = new InitCommand($config);
        $tester = new CommandTester($command);

        // Ensure .mate directory doesn't exist
        $this->assertDirectoryDoesNotExist($this->tempDir.'/.mate');

        $tester->execute([]);

        // Directory should be created
        $this->assertDirectoryExists($this->tempDir.'/.mate');
        $this->assertFileExists($this->tempDir.'/.mate/extensions.php');
    }

    private function createConfiguration(): Configuration
    {
        return Configuration::fromArray([
            'root_dir' => $this->tempDir,
            'cache_dir' => sys_get_temp_dir().'/mate',
            'scan_dirs' => ['mate'],
            'env_file' => null,
            'enabled_plugins' => [],
        ]);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
