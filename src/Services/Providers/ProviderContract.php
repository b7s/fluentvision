<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services\Providers;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;

interface ProviderContract
{
    public function name(): string;

    public function scriptPath(): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function buildArguments(
        string $mediaPath,
        MediaType $mediaType,
        string $model,
        Device $device,
        array $options = [],
    ): array;

    public function supportsVideo(): bool;

    public function supportsStream(): bool;
}
