<?php

namespace Symfony\AI\Mate\Tests\Helper;

use PHPUnit\Framework\TestCase;

class McpLoadEnvTest extends TestCase
{
    private string $tempDir;
    private string $originalMateRootDir;

    protected function setUp(): void
    {
        // Create temporary directory for testing
        $this->tempDir = sys_get_temp_dir().'/mate-loadenv-test-'.uniqid();
        mkdir($this->tempDir.'/.mate', 0755, true);

        // Save original MATE_ROOT_DIR
        $this->originalMateRootDir = getenv('MATE_ROOT_DIR') ?: '';

        // Set MATE_ROOT_DIR to temp directory
        putenv('MATE_ROOT_DIR='.$this->tempDir);
    }

    protected function tearDown(): void
    {
        // Restore original MATE_ROOT_DIR
        putenv('MATE_ROOT_DIR='.$this->originalMateRootDir);

        // Clean up temporary directory
        $this->removeDirectory($this->tempDir);

        // Clean up any environment variables set during tests
        putenv('TEST_VAR=');
        putenv('ANOTHER_VAR=');
    }

    public function testLoadsEnvironmentFile(): void
    {
        // Create a test .env file
        file_put_contents($this->tempDir.'/.mate/.env', "TEST_VAR=test_value\nANOTHER_VAR=another_value\n");

        // Load the env file
        mcpLoadEnv('.env');

        // Verify variables are loaded (Symfony Dotenv loads into $_ENV and $_SERVER)
        $this->assertSame('test_value', $_ENV['TEST_VAR']);
        $this->assertSame('another_value', $_ENV['ANOTHER_VAR']);
    }

    public function testThrowsExceptionWhenMateRootDirNotSet(): void
    {
        putenv('MATE_ROOT_DIR=');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MATE_ROOT_DIR environment variable is not set');

        mcpLoadEnv('.env');
    }

    public function testThrowsExceptionWhenMateDirDoesNotExist(): void
    {
        // Remove .mate directory
        rmdir($this->tempDir.'/.mate');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The \.mate directory does not exist/');

        mcpLoadEnv('.env');
    }

    public function testThrowsExceptionWhenEnvFileDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The environment file does not exist/');

        mcpLoadEnv('.env.nonexistent');
    }

    public function testStripsLeadingSlashFromFilename(): void
    {
        // Create a test .env file
        file_put_contents($this->tempDir.'/.mate/.env.test', "TEST_VAR=value\n");

        // Load with leading slash
        mcpLoadEnv('/.env.test');

        // Verify it loaded correctly
        $this->assertSame('value', $_ENV['TEST_VAR']);
    }

    public function testLoadsCustomEnvFile(): void
    {
        // Create a custom .env file
        file_put_contents($this->tempDir.'/.mate/.env.local', "LOCAL_VAR=local_value\n");

        // Load the custom env file
        mcpLoadEnv('.env.local');

        // Verify variable is loaded
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
