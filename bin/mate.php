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

// Set root directory as environment variable for container
$_ENV=['MATE_ROOT_DIR'] = $root

use Symfony\AI\Mate\App;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;

// Build container with default services
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/src'));
$loader->load('default.services.php');

// Load user services if exists
if (file_exists($root.'/.mate/services.php')) {
    $userLoader = new PhpFileLoader($container, new FileLocator($root.'/.mate'));
    $userLoader->load('services.php');
}

// Read enabled extensions
$enabledPlugins = [];
if (file_exists($root.'/.mate/extensions.php')) {
    $extensionsConfig = include $root.'/.mate/extensions.php';
    if (is_array($extensionsConfig)) {
        foreach ($extensionsConfig as $packageName => $config) {
            if (is_string($packageName) && is_array($config) && ($config['enabled'] ?? false)) {
                $enabledPlugins[] = $packageName;
            }
        }
    }
}

$container->setParameter('mate.enabled_plugins', $enabledPlugins);
$container->setParameter('mate.root_dir', $root);

App::build($container)->run();
