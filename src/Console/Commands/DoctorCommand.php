<?php

declare(strict_types=1);

namespace B7s\FluentVision\Console\Commands;

use B7s\FluentVision\Config;
use B7s\FluentVision\Services\PythonService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function is_dir;
use function is_writable;
use function sprintf;

#[AsCommand(
    name: 'doctor',
    description: 'Check FluentVision environment and dependencies',
)]
class DoctorCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Path to config file',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $configOption = $input->getOption('config');
        $config = new Config(is_string($configOption) ? $configOption : null);

        $io->title('FluentVision Doctor');

        $errors = 0;
        $warnings = 0;

        $errors += $this->checkPython($io, $config);
        $errors += $this->checkVenv($io, $config);
        $warnings += $this->checkPackages($io, $config);
        $errors += $this->checkModelDir($io, $config);
        $warnings += $this->checkNanodetRepo($io, $config);
        $warnings += $this->checkConfig($io, $config);

        $io->section('Summary');

        if ($errors > 0) {
            $io->error(sprintf('%d error(s) found. Fix them before using FluentVision.', $errors));

            return Command::FAILURE;
        }

        if ($warnings > 0) {
            $io->warning(sprintf('%d warning(s) found. FluentVision will work but some features may be limited.', $warnings));
        } else {
            $io->success('All checks passed. FluentVision is ready to use!');
        }

        return Command::SUCCESS;
    }

    private function checkPython(SymfonyStyle $io, Config $config): int
    {
        $io->section('Python Interpreter');

        $pythonService = new PythonService(
            configPythonPath: $config->pythonPath(),
            venvPath: $config->pythonVenvPath(),
        );

        try {
            $path = $pythonService->resolvePythonPath();
            $version = $pythonService->getPythonVersion();

            $io->text(sprintf('  <info>✓</info> Python found: %s', $path));
            $io->text(sprintf('  <info>✓</info> Version: %s', $version ?? 'unknown'));

            return 0;
        } catch (Throwable $e) {
            $io->text(sprintf('  <error>✗</error> %s', $e->getMessage()));

            return 1;
        }
    }

    private function checkVenv(SymfonyStyle $io, Config $config): int
    {
        $io->section('Python Virtual Environment');

        $venvPath = $config->pythonVenvPath();
        $venvPathString = $venvPath ?? '';

        if ($venvPath !== null && is_dir($venvPath)) {
            $io->text(sprintf(' <info>✓</info> Venv exists: %s', $venvPathString));

            return 0;
        }

        $io->text(sprintf(' <comment>!</comment> Venv not found at: %s', $venvPathString));
        $io->text(' Run <comment>fluentvision install</comment> to set up the virtual environment.');

        return 0;
    }

    private function checkPackages(SymfonyStyle $io, Config $config): int
    {
        $io->section('Python Packages');
        $warnings = 0;

        $pythonService = new PythonService(
            configPythonPath: $config->pythonPath(),
            venvPath: $config->pythonVenvPath(),
        );

        $packages = [
            'ultralytics' => 'Ultralytics YOLO',
            'cv2' => 'OpenCV (cv2)',
            'yaml' => 'PyYAML',
        ];

        foreach ($packages as $package => $label) {
            if ($pythonService->isPackageInstalled($package)) {
                $io->text(sprintf('  <info>✓</info> %s installed', $label));
            } else {
                $io->text(sprintf('  <comment>!</comment> %s not installed', $label));
                $warnings++;
            }
        }

        return $warnings;
    }

    private function checkModelDir(SymfonyStyle $io, Config $config): int
    {
        $io->section('Model Directory');

        $modelDir = $config->modelDir();

        if (is_dir($modelDir)) {
            $io->text(sprintf('  <info>✓</info> Model directory: %s', $modelDir));

            if (is_writable($modelDir)) {
                $io->text('  <info>✓</info> Directory is writable');
            } else {
                $io->text('  <error>✗</error> Directory is not writable');

                return 1;
            }

            return 0;
        }

        $io->text(sprintf('  <comment>!</comment> Model directory not found: %s', $modelDir));
        $io->text('  Run <comment>fluentvision install</comment> to create it.');

        return 0;
    }

    private function checkNanodetRepo(SymfonyStyle $io, Config $config): int
    {
        $io->section('NanoDet Repository');

        $repoPath = $config->nanodetRepoPath();

        if (is_dir($repoPath)) {
            $io->text(sprintf('  <info>✓</info> NanoDet repo: %s', $repoPath));

            return 0;
        }

        $io->text(sprintf('  <comment>!</comment> NanoDet repo not found at: %s', $repoPath));
        $io->text('  Run <comment>fluentvision install</comment> to clone it.');

        return 1;
    }

    private function checkConfig(SymfonyStyle $io, Config $config): int
    {
        $io->section('Configuration');

        $provider = $config->defaultProvider();
        $io->text(sprintf('  <info>✓</info> Default provider: %s', $provider));

        return 0;
    }
}
