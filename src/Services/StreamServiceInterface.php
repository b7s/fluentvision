<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\StreamResult;

interface StreamServiceInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @param  callable(InferenceResult $frame, int $frameNumber, StreamResult $result): void  $onFrame
     */
    public function stream(
        Provider $providerType,
        string $source,
        string $model,
        Device $device,
        callable $onFrame,
        array $options = [],
    ): StreamResult;
}
