<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Mate\Mcp\Tools;

use Mcp\Capability\Attribute\McpTool;

class ServerInfo
{
    #[McpTool('php-version', 'Get the version of PHP')]
    public function phpVersion(): string
    {
        return \PHP_VERSION;
    }

    #[McpTool('operating-system', 'Get the current operating system')]
    public function operatingSystem(): string
    {
        return \PHP_OS;
    }

    #[McpTool('operating-system-family', 'Get the current operating system family')]
    public function operatingSystemFamily(): string
    {
        return \PHP_OS_FAMILY;
    }

    /**
     * @return string[]
     */
    #[McpTool('php-extensions', 'Get a list of PHP extensions')]
    public function extensions(): array
    {
        return get_loaded_extensions();
    }
}
