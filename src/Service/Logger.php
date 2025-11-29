<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Service;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $debug = $_SERVER['DEBUG'] ?? false;

        if (! $debug && 'debug' === $level) {
            return;
        }

        $logMessage = sprintf(
            "[%s] %s %s\n",
            strtoupper($level),
            $message,
            ([] === $context || ! $debug) ? '' : json_encode($context),
        );

        if (($_SERVER['FILE_LOG'] ?? false) || ! defined('STDERR')) {
            file_put_contents('dev.log', $logMessage, \FILE_APPEND);
        } else {
            fwrite(\STDERR, $logMessage);
        }
    }
}
