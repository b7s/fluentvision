<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Exceptions\ModelNotFoundException;

use function file_exists;
use function sprintf;

readonly class ModelService
{
    public function __construct(
        private string $modelDir,
        private string $nanodetRepoPath = '',
    ) {}

    public function resolveUltralyticsModel(YoloModel $model): string
    {
        $path = sprintf('%s/%s', $this->modelDir, $model->filename());

        if (file_exists($path)) {
            return $path;
        }

        return $model->value;
    }

    /**
     * @return array<string, string>
     */
    public function resolveNanodetModel(NanodetModel $model): array
    {
        $configPath = sprintf('%s/%s', $this->nanodetRepoPath, $model->repoConfigPath());

        if (! file_exists($configPath)) {
            throw ModelNotFoundException::fromPath($configPath);
        }

        $checkpointPath = sprintf('%s/%s', $this->modelDir, $model->repoCheckpointName());

        if (! file_exists($checkpointPath)) {
            throw ModelNotFoundException::fromPath($checkpointPath);
        }

        return [
            'config' => $configPath,
            'checkpoint' => $checkpointPath,
        ];
    }

    public function getModelDir(): string
    {
        return $this->modelDir;
    }

    public function modelExists(string $filename): bool
    {
        return file_exists(sprintf('%s/%s', $this->modelDir, $filename));
    }

    public function nanodetModelExists(NanodetModel $model): bool
    {
        $configPath = sprintf('%s/%s', $this->nanodetRepoPath, $model->repoConfigPath());

        if (! file_exists($configPath)) {
            return false;
        }

        $checkpointPath = sprintf('%s/%s', $this->modelDir, $model->repoCheckpointName());

        return file_exists($checkpointPath);
    }

    public function getNanodetRepoPath(): string
    {
        return $this->nanodetRepoPath;
    }
}
