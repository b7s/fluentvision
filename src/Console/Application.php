<?php

declare(strict_types=1);

namespace B7s\FluentVision\Console;

use B7s\FluentVision\Console\Commands\DoctorCommand;
use B7s\FluentVision\Console\Commands\InstallCommand;
use Symfony\Component\Console\Application as BaseApplication;

use function file_get_contents;

class Application extends BaseApplication
{
    public function __construct()
    {
        $version = $this->loadVersion();

        parent::__construct('FluentVision', $version);

        $this->addCommand(new DoctorCommand);
        $this->addCommand(new InstallCommand);
    }

    private function loadVersion(): string
    {
        $versionFile = dirname(__DIR__, 2).'/version';
        $version = file_exists($versionFile) ? file_get_contents($versionFile) : '0.1.0';

        return trim(is_string($version) ? $version : '0.1.0');
    }
}
