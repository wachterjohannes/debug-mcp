<?php

declare(strict_types=1);

namespace Symfony\AiMate\Discovery;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

/**
 * Discovers MCP extensions from local mcp/ directory.
 *
 * Scans PHP files in the mcp/ directory and uses reflection to find
 * classes containing MCP attributes (#[McpTool], #[McpResource], #[McpPrompt]).
 * Enables rapid prototyping without requiring Composer packages.
 */
class LocalDiscovery
{
    private string $mcpDir;

    public function __construct(?string $mcpDir = null)
    {
        $this->mcpDir = $mcpDir ?? dirname(__DIR__, 2) . '/mcp';
    }

    /**
     * Scan local mcp/ directory for classes with MCP attributes.
     *
     * @return array<string> Fully-qualified class names
     */
    public function scan(): array
    {
        if (! is_dir($this->mcpDir)) {
            return [];
        }

        $classes = [];
        $files = $this->findPhpFiles($this->mcpDir);

        foreach ($files as $file) {
            require_once $file;
        }

        $declaredClasses = get_declared_classes();

        foreach ($declaredClasses as $className) {
            if ($this->hasMcpAttributes($className)) {
                $classes[] = $className;
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * Find all PHP files in directory recursively.
     *
     * @return array<string>
     */
    private function findPhpFiles(string $directory): array
    {
        $phpFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    /**
     * Check if class has MCP attributes.
     */
    private function hasMcpAttributes(string $className): bool
    {
        try {
            $reflection = new ReflectionClass($className);

            // Check if class methods have MCP attributes
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes();
                foreach ($attributes as $attribute) {
                    $attributeName = $attribute->getName();
                    if (
                        str_contains($attributeName, 'McpTool') ||
                        str_contains($attributeName, 'McpResource') ||
                        str_contains($attributeName, 'McpPrompt')
                    ) {
                        return true;
                    }
                }
            }
        } catch (\ReflectionException $e) {
            return false;
        }

        return false;
    }
}
