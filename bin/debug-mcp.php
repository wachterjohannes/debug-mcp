<?php

declare(strict_types=1);

/**
 * debug-mcp entry point
 *
 * Starts the MCP server with stdio transport for JSON-RPC communication.
 */


$autoloadPaths = [
    __DIR__ . '/../../../autoload.php',  // Project autoloader (preferred)
    __DIR__ . '/../vendor/autoload.php', // Package autoloader (fallback)
];

$userConfig = [];
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $root = dirname(realpath($autoloadPath), 2);
        if (file_exists($root . '/.mcp.php')) {
            $userConfig = include $root . '/.mcp.php';
        }
        break;
    }
}

$config = include dirname(__DIR__).'/src/default.config.php';

use Wachterjohannes\DebugMcp\App;

// Create and run server
$app = App::build(array_merge(['rootDir'=>$root], $config, $userConfig));
$app->run();
