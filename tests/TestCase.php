<?php

declare(strict_types=1);

namespace B7s\FluentVision\Tests;

use B7s\FluentVision\Config;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function createTestConfig(): Config
    {
        return new Config(__DIR__.'/../fixtures/test-config.php');
    }
}
