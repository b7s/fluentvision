<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Exceptions\InferenceException;
use B7s\FluentVision\Exceptions\PythonNotFoundException;
use Symfony\Component\Process\Process;

use function array_merge;
use function file_exists;
use function is_executable;
use function sprintf;

class PythonService
{
    private ?string $pythonPath = null;

    public function __construct(
        private readonly ?string $configPythonPath = null,
        private readonly ?string $venvPath = null,
        private readonly int $timeout = 0,
    ) {}

    public function resolvePythonPath(): string
    {
        if ($this->pythonPath !== null) {
            return $this->pythonPath;
        }

        $candidates = $this->getCandidatePaths();

        foreach ($candidates as $path) {
            if (file_exists($path) && is_executable($path)) {
                $this->pythonPath = $path;

                return $path;
            }
        }

        throw PythonNotFoundException::fromPaths($candidates);
    }

    /**
     * @param  array<int, string>  $arguments
     */
    public function executeScript(string $scriptPath, array $arguments = []): string
    {
        $python = $this->resolvePythonPath();
        $command = array_merge([$python, $scriptPath], $arguments);

        $process = new Process($command);
        $process->setTimeout($this->timeout > 0 ? $this->timeout : null);

        $process->run();

        if (! $process->isSuccessful()) {
            throw InferenceException::fromProcessError($scriptPath, $process->getErrorOutput());
        }

        $output = $process->getOutput();

        if ($output === '') {
            throw InferenceException::fromInvalidOutput('Empty output from Python script');
        }

        return $output;
    }

    public function isPackageInstalled(string $package): bool
    {
        $python = $this->resolvePythonPath();

        $process = new Process([$python, '-c', sprintf('import %s', $package)]);
        $process->run();

        return $process->isSuccessful();
    }

    public function installPackage(string $package, int $timeout = 0): bool
    {
        $python = $this->resolvePythonPath();
        $pip = $this->resolvePipPath();

        $process = new Process([...$pip, 'install', $package]);
        $process->setTimeout($timeout > 0 ? $timeout : null);
        $process->run();

        return $process->isSuccessful();
    }

    public function getPythonVersion(): ?string
    {
        $python = $this->resolvePythonPath();

        $process = new Process([$python, '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        return trim($process->getOutput());
    }

    public function reset(): void
    {
        $this->pythonPath = null;
    }

    /**
     * @return array<int, string>
     */
    private function getCandidatePaths(): array
    {
        $candidates = [];

        if ($this->configPythonPath !== null && $this->configPythonPath !== '') {
            $candidates[] = $this->configPythonPath;
        }

        $home = $this->getHomeDir();
        $venvPath = $this->venvPath ?? ($home.'/.fluentvision/venv');

        $candidates[] = $venvPath.'/bin/python';
        $candidates[] = '/usr/bin/python3';
        $candidates[] = '/usr/local/bin/python3';
        $candidates[] = '/usr/bin/python';
        $candidates[] = '/usr/local/bin/python';

        return $candidates;
    }

    /**
     * @return array<int, string>
     */
    private function resolvePipPath(): array
    {
        $python = $this->resolvePythonPath();
        $pipPath = str_replace('python', 'pip', $python);

        if (file_exists($pipPath) && is_executable($pipPath)) {
            return [$pipPath];
        }

        return [$python, '-m', 'pip'];
    }

    private function getHomeDir(): string
    {
        $home = $_SERVER['HOME'] ?? '';

        return is_string($home) ? $home : '';
    }
}
