<?php

namespace Symfony\AI\Mate\Tests\Helper;

use PHPUnit\Framework\TestCase;

class MateLoadEnvTest extends TestCase
{
    private string $tempDir;
    private string $originalMateRootDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/mate-loadenv-test-'.uniqid();
        mkdir($this->tempDir.'/.mate', 0755, true);

        $this->originalMateRootDir = \is_string($_ENV['MATE_ROOT_DIR'] ?? null) ? $_ENV['MATE_ROOT_DIR'] : '';

        $_ENV['MATE_ROOT_DIR'] = $this->tempDir;
    }

    protected function tearDown(): void
    {
        $_ENV['MATE_ROOT_DIR'] = $this->originalMateRootDir;

        $this->removeDirectory($this->tempDir);

        $_ENV['TEST_VAR'] = $_ENV['ANOTHER_VAR'] = '';
    }

    public function testLoadsEnvironmentFile(): void
    {
        file_put_contents($this->tempDir.'/.mate/.env', "TEST_VAR=test_value\nANOTHER_VAR=another_value\n");

        mateLoadEnv('.env');

        $this->assertSame('test_value', $_ENV['TEST_VAR']);
        $this->assertSame('another_value', $_ENV['ANOTHER_VAR']);
    }

    public function testThrowsExceptionWhenMateRootDirNotSet(): void
    {
        $_ENV['MATE_ROOT_DIR'] = '';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MATE_ROOT_DIR environment variable is not set');

        mateLoadEnv('.env');
    }

    public function testThrowsExceptionWhenMateDirDoesNotExist(): void
    {
        rmdir($this->tempDir.'/.mate');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The \.mate directory does not exist/');

        mateLoadEnv('.env');
    }

    public function testThrowsExceptionWhenEnvFileDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The environment file does not exist/');

        mateLoadEnv('.env.nonexistent');
    }

    public function testStripsLeadingSlashFromFilename(): void
    {
        file_put_contents($this->tempDir.'/.mate/.env.test', "TEST_VAR=value\n");

        mateLoadEnv('/.env.test');

        // Verify it loaded correctly
        $this->assertSame('value', $_ENV['TEST_VAR']);
    }

    public function testLoadsCustomEnvFile(): void
    {
        file_put_contents($this->tempDir.'/.mate/.env.local', "LOCAL_VAR=local_value\n");

        mateLoadEnv('.env.local');

        $this->assertSame('local_value', $_ENV['LOCAL_VAR']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
