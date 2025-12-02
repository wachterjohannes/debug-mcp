<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Mate\Bridge\Monolog\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Mate\Bridge\Monolog\Capability\LogSearchTool;
use Symfony\AI\Mate\Bridge\Monolog\Service\LogParser;
use Symfony\AI\Mate\Bridge\Monolog\Service\LogReader;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class LogSearchToolTest extends TestCase
{
    private LogSearchTool $tool;

    protected function setUp(): void
    {
        $fixturesDir = \dirname(__DIR__).'/Fixtures';
        $reader = new LogReader(new LogParser(), $fixturesDir);
        $this->tool = new LogSearchTool($reader);
    }

    public function testSearch(): void
    {
        $results = $this->tool->search('database');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Database', $results[0]['message']);
    }

    public function testSearchWithLevel(): void
    {
        $results = $this->tool->search('', 'ERROR');

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame('ERROR', $result['level']);
        }
    }

    public function testSearchWithChannel(): void
    {
        $results = $this->tool->search('', null, 'security');

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame('security', $result['channel']);
        }
    }

    public function testSearchWithLimit(): void
    {
        $results = $this->tool->search('', limit: 3);

        $this->assertCount(3, $results);
    }

    public function testSearchRegex(): void
    {
        $results = $this->tool->searchRegex('/connection|timeout/i');

        $this->assertGreaterThanOrEqual(1, \count($results));
    }

    public function testSearchContext(): void
    {
        $results = $this->tool->searchContext('user_id', '123');

        $this->assertCount(1, $results);
        $this->assertSame(123, $results[0]['context']['user_id']);
    }

    public function testTail(): void
    {
        $results = $this->tool->tail(5);

        $this->assertCount(5, $results);
    }

    public function testListFiles(): void
    {
        $files = $this->tool->listFiles();

        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertArrayHasKey('name', $file);
            $this->assertArrayHasKey('path', $file);
            $this->assertArrayHasKey('size', $file);
            $this->assertArrayHasKey('modified', $file);
        }
    }

    public function testListChannels(): void
    {
        $channels = $this->tool->listChannels();

        $this->assertContains('app', $channels);
        $this->assertContains('security', $channels);
    }

    public function testByLevel(): void
    {
        $results = $this->tool->byLevel('WARNING');

        $this->assertGreaterThanOrEqual(1, \count($results));
        foreach ($results as $result) {
            $this->assertSame('WARNING', $result['level']);
        }
    }
}
