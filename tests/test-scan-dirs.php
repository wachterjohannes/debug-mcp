#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\AI\Mate\Model\Configuration;
use Symfony\AI\Mate\Discovery\ComposerTypeDiscovery;
use Symfony\AI\Mate\Service\Logger;

$rootDir = dirname(__DIR__);
$defaults = require $rootDir . '/src/default.config.php';
$userConfig = file_exists($rootDir . '/.mcp.php') ? require $rootDir . '/.mcp.php' : [];

$config = Configuration::fromArray(array_merge(['rootDir' => $rootDir], $defaults, $userConfig));
$logger = new Logger();
$discovery = new ComposerTypeDiscovery($config->rootDir, $logger);

echo "=== MCP Discovery & Scan Directories Test ===\n\n";

echo "1. Root directory: {$config->rootDir}\n";
echo "2. Cache directory: {$config->cacheDir}\n";
echo "3. Enabled plugins: " . json_encode($config->enabledPlugins) . "\n\n";

echo "4. Discovered Composer extensions:\n";
$extensions = $discovery->discover($config->enabledPlugins);
if (empty($extensions)) {
    echo "   (none found)\n\n";
} else {
    foreach ($extensions as $package => $dirs) {
        echo "   - $package:\n";
        foreach ($dirs as $dir) {
            echo "     * $dir\n";
        }
    }
    echo "\n";
}

echo "5. All scan directories:\n";
$scanDirs = [];

// Add discovered extensions
foreach ($extensions as $dirs) {
    foreach ($dirs as $dir) {
        $scanDirs[] = $dir;
    }
}

// Add configured scan dirs
foreach ($config->scanDirs as $dir) {
    $dir = trim($dir);
    if ('' !== $dir) {
        $scanDirs[] = $dir;
    }
}

// Add local mcp/ directory
$scanDirs[] = substr(dirname(__DIR__) . '/mcp', strlen($config->rootDir));

foreach ($scanDirs as $dir) {
    $fullPath = $config->rootDir . '/' . $dir;
    $exists = is_dir($fullPath) ? '✓' : '✗';
    echo "   $exists $dir";
    if (is_dir($fullPath)) {
        $count = iterator_count(new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
        ));
        echo " ($count files)";
    }
    echo "\n";
}

echo "\n6. MCP capabilities found in local mcp/ directory:\n";
$mcpDir = dirname(__DIR__) . '/mcp';
if (is_dir($mcpDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($mcpDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && 'php' === $file->getExtension()) {
            $content = file_get_contents($file->getPathname());
            if (str_contains($content, '#[McpTool') ||
                str_contains($content, '#[McpResource') ||
                str_contains($content, '#[McpPrompt')) {

                $relativePath = str_replace($mcpDir . '/', '', $file->getPathname());
                echo "   ✓ $relativePath\n";

                // Count attributes - match both old style #[McpTool('name')] and new style #[McpTool(name: 'name')]
                $tools = preg_match_all('/#\[McpTool/i', $content);
                $resources = preg_match_all('/#\[McpResource/i', $content);
                $prompts = preg_match_all('/#\[McpPrompt/i', $content);

                if ($tools > 0) echo "     - $tools tool(s)\n";
                if ($resources > 0) echo "     - $resources resource(s)\n";
                if ($prompts > 0) echo "     - $prompts prompt(s)\n";
            }
        }
    }
} else {
    echo "   ✗ mcp/ directory not found\n";
}

echo "\n✅ Discovery test complete\n";
