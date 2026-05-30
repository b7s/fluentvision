<?php

declare(strict_types=1);

namespace B7s\FluentVision\Console\Commands;

use B7s\FluentVision\Config;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Services\PythonService;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

use function file_exists;
use function is_dir;
use function mkdir;
use function sprintf;

#[AsCommand(
    name: 'install',
    description: 'Install FluentVision dependencies (Python venv, packages, models)',
)]
class InstallCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Path to config file',
        );

        $this->addOption(
            'provider',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Provider to install (ultralytics, nanodet, all)',
            'all',
        );

        $this->addOption(
            'model',
            'm',
            InputOption::VALUE_OPTIONAL,
            'Model to download',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $configOption = $input->getOption('config');
        $config = new Config(is_string($configOption) ? $configOption : null);
        $providerRaw = $input->getOption('provider');
        $provider = is_string($providerRaw) ? $providerRaw : 'all';

        $io->title('FluentVision Installer');

        $this->createDirectories($io, $config);
        $this->createVenv($io, $config);

        if ($provider === 'all' || $provider === 'ultralytics') {
            $this->installUltralytics($io, $config);
        }

        if ($provider === 'all' || $provider === 'nanodet') {
            $this->installNanodet($io, $config);
        }

        $modelOption = $input->getOption('model');
        if (is_string($modelOption) && $modelOption !== '') {
            $this->downloadModel($io, $config, $modelOption);
        }

        $io->success('FluentVision installation complete!');

        return Command::SUCCESS;
    }

    private function createDirectories(SymfonyStyle $io, Config $config): void
    {
        $io->section('Creating Directories');

        $dirs = [
            $config->modelDir(),
            $config->modelDir().'/nanodet',
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
                }
                $io->text(sprintf(' <info>✓</info> Created: %s', $dir));
            } else {
                $io->text(sprintf(' <info>✓</info> Exists: %s', $dir));
            }
        }
    }

    private function createVenv(SymfonyStyle $io, Config $config): void
    {
        $io->section('Python Virtual Environment');

        $venvPath = $config->pythonVenvPath();
        $venvPathString = $venvPath ?? '';

        if ($venvPath !== null && is_dir($venvPath)) {
            $io->text(sprintf(' <info>✓</info> Venv already exists: %s', $venvPathString));

            return;
        }

        $io->text(sprintf(' Creating venv at: %s', $venvPathString));

        $process = new Process(['python3', '-m', 'venv', $venvPathString]);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $io->text(sprintf(' <info>✓</info> Venv created: %s', $venvPathString));
        } else {
            $io->text(sprintf(' <error>✗</error> Failed to create venv: %s', $process->getErrorOutput()));
        }
    }

    private function installUltralytics(SymfonyStyle $io, Config $config): void
    {
        $io->section('Installing Ultralytics');

        $pythonService = new PythonService(
            configPythonPath: $config->pythonPath(),
            venvPath: $config->pythonVenvPath(),
        );

        $this->installPackages($io, $pythonService, ['ultralytics', 'opencv-python-headless', 'pyyaml']);
    }

    private function installNanodet(SymfonyStyle $io, Config $config): void
    {
        $io->section('Installing NanoDet');

        $repoPath = $config->nanodetRepoPath();

        if ($repoPath !== '' && ! is_dir($repoPath)) {
            $io->text(' Cloning NanoDet repository...');

            $process = new Process([
                'git', 'clone', NanodetModel::repoCloneUrl(), $repoPath,
            ]);
            $process->setTimeout(300);
            $process->run();

            if ($process->isSuccessful()) {
                $io->text(sprintf(' <info>✓</info> NanoDet cloned to: %s', $repoPath));
            } else {
                $io->text(sprintf(' <error>✗</error> Failed to clone NanoDet: %s', $process->getErrorOutput()));
            }
        } else {
            $io->text(sprintf(' <info>✓</info> NanoDet repo already exists: %s', $repoPath));
        }

        $pythonService = new PythonService(
            configPythonPath: $config->pythonPath(),
            venvPath: $config->pythonVenvPath(),
        );

        $this->installPackages($io, $pythonService, ['opencv-python-headless', 'pyyaml', 'torch', 'torchvision']);
    }

    /**
     * @param  array<string>  $packages
     */
    private function installPackages(SymfonyStyle $io, PythonService $pythonService, array $packages): void
    {
        foreach ($packages as $package) {
            $io->text(sprintf(' Installing %s...', $package));

            if ($pythonService->installPackage($package)) {
                $io->text(sprintf(' <info>✓</info> %s installed', $package));
            } else {
                $io->text(sprintf(' <error>✗</error> Failed to install %s', $package));
            }
        }
    }

    private function downloadModel(SymfonyStyle $io, Config $config, string $model): void
    {
        $io->section(sprintf('Downloading Model: %s', $model));

        $modelDir = $config->modelDir();

        $yoloModel = YoloModel::tryFrom($model);
        $nanodetModel = NanodetModel::tryFrom($model);

        if ($yoloModel !== null) {
            $this->downloadYoloModel($io, $modelDir, $yoloModel);

            return;
        }

        if ($nanodetModel !== null) {
            $this->downloadNanodetModel($io, $modelDir, $nanodetModel);

            return;
        }

        $this->downloadGenericModel($io, $modelDir, $model);
    }

    private function downloadYoloModel(SymfonyStyle $io, string $modelDir, YoloModel $model): void
    {
        $destPath = sprintf('%s/%s', $modelDir, $model->filename());

        if (file_exists($destPath)) {
            $io->text(sprintf(' <info>✓</info> Model already exists: %s', $destPath));

            return;
        }

        $this->downloadWithCurl($io, $model->downloadUrl(), $destPath, 600, 'Model');
    }

    private function downloadNanodetModel(SymfonyStyle $io, string $modelDir, NanodetModel $model): void
    {
        $modelSubDir = sprintf('%s/%s', $modelDir, $model->dirname());

        if (! is_dir($modelSubDir)) {
            if (! mkdir($modelSubDir, 0755, true) && ! is_dir($modelSubDir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $modelSubDir));
            }
        }

        $configPath = sprintf('%s/%s', $modelSubDir, $model->configFilename());
        $checkpointPath = sprintf('%s/%s', $modelSubDir, $model->checkpointFilename());

        if (! file_exists($configPath)) {
            $this->downloadWithCurl($io, $model->configUrl(), $configPath, 120, 'Config');
        } else {
            $io->text(sprintf(' <info>✓</info> Config already exists: %s', $configPath));
        }

        if (! file_exists($checkpointPath)) {
            $this->downloadWithCurl($io, $model->checkpointUrl(), $checkpointPath, 600, 'Checkpoint');
        } else {
            $io->text(sprintf(' <info>✓</info> Checkpoint already exists: %s', $checkpointPath));
        }
    }

    private function downloadGenericModel(SymfonyStyle $io, string $modelDir, string $model): void
    {
        $destPath = sprintf('%s/%s', $modelDir, $model);

        if (file_exists($destPath)) {
            $io->text(sprintf(' <info>✓</info> Model already exists: %s', $destPath));

            return;
        }

        $url = sprintf('https://github.com/ultralytics/assets/releases/download/v8.4.0/%s', $model);
        $this->downloadWithCurl($io, $url, $destPath, 600, 'Model');
    }

    private function downloadWithCurl(SymfonyStyle $io, string $url, string $destPath, int $timeout, string $label): void
    {
        $io->text(sprintf(' Downloading %s from: %s', $label, $url));

        $process = new Process(['curl', '-L', '-o', $destPath, $url]);
        $process->setTimeout($timeout);
        $process->run();

        if ($process->isSuccessful()) {
            $io->text(sprintf(' <info>✓</info> %s downloaded: %s', $label, $destPath));
        } else {
            $io->text(sprintf(' <error>✗</error> Failed to download %s: %s', $label, $process->getErrorOutput()));
        }
    }
}
