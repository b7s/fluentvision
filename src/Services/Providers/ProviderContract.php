<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services\Providers;

use B7s\FluentVision\Enums\Device;

interface ProviderContract
{
    public function name(): string;

    public function scriptPath(): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function buildArguments(
        string $imagePath,
        string $model,
        Device $device,
        array $options = [],
    ): array;

    public function supportsVideo(): bool;

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function buildVideoArguments(
        string $videoPath,
        string $model,
        Device $device,
        array $options = [],
    ): array;
}
