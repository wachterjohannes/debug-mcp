<?php

declare(strict_types=1);

/**
 * Symfony AI Mate entry point
 *
 * Starts the MCP server with stdio transport for JSON-RPC communication.
 */


$autoloadPaths = [
    __DIR__ . '/../../../autoload.php',  // Project autoloader (preferred)
    __DIR__ . '/../vendor/autoload.php', // Package autoloader (fallback)
];

$root = null;
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

if (!$root) {
    echo 'Unable to locate the Composer vendor directory. Did you run composer install?'.PHP_EOL;
    exit(1);
}

$config = include dirname(__DIR__).'/src/default.config.php';

use Symfony\AI\Mate\App;
use Symfony\AI\Mate\Model\Configuration;

$config = Configuration::fromArray(array_merge(['root_dir' => $root], $config, $userConfig));

App::build($config)->run();
