<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\YoloModel;

interface ModelServiceInterface
{
    public function resolveUltralyticsModel(YoloModel $model): string;

    /**
     * @return array<string, string>
     */
    public function resolveNanodetModel(NanodetModel $model): array;

    public function getModelDir(): string;

    public function modelExists(string $filename): bool;

    public function nanodetModelExists(NanodetModel $model): bool;

    public function getNanodetRepoPath(): string;
}
