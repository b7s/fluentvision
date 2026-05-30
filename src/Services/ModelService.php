<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Exceptions\ModelNotFoundException;

use function file_exists;
use function is_dir;
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
        $modelDir = sprintf('%s/%s', $this->modelDir, $model->dirname());

        if (! is_dir($modelDir)) {
            throw ModelNotFoundException::fromName($model->dirname());
        }

        $configPath = sprintf('%s/%s', $modelDir, $model->configFilename());
        $checkpointPath = sprintf('%s/%s', $modelDir, $model->checkpointFilename());

        if (! file_exists($configPath)) {
            throw ModelNotFoundException::fromPath($configPath);
        }

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
        $modelDir = sprintf('%s/%s', $this->modelDir, $model->dirname());

        if (! is_dir($modelDir)) {
            return false;
        }

        $configPath = sprintf('%s/%s', $modelDir, $model->configFilename());
        $checkpointPath = sprintf('%s/%s', $modelDir, $model->checkpointFilename());

        return file_exists($configPath) && file_exists($checkpointPath);
    }

    public function getNanodetRepoPath(): string
    {
        return $this->nanodetRepoPath;
    }
}
