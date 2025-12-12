<?php

declare(strict_types=1);

/**
 * Symfony AI Mate entry point
 *
 * Starts the MCP server with stdio transport for JSON-RPC communication.
 */

$autoloadPaths = [
    getcwd() . '/vendor/autoload.php',  // Project autoloader using current-working-directory (preferred)
    __DIR__ . '/../../../autoload.php',  // Project autoloader
    __DIR__ . '/../vendor/autoload.php', // Package autoloader (fallback)
];

$root = null;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $root = dirname(realpath($autoloadPath), 2);
        break;
    }
}

if (!$root) {
    echo 'Unable to locate the Composer vendor directory. Did you run composer install?'.PHP_EOL;
    exit(1);
}

// Set the root directory as an environment variable using $_ENV to be thread-safe
$_ENV['MATE_ROOT_DIR'] = $root;

use Symfony\AI\Mate\App;
use Symfony\AI\Mate\Container\ContainerFactory;

$containerFactory = new ContainerFactory($root);
$container = $containerFactory->create();

App::build($container)->run();
