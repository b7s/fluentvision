<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\VideoInferenceResult;

interface InferenceServiceInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function detect(
        Provider $providerType,
        string $mediaPath,
        MediaType $mediaType,
        string $model,
        Device $device,
        array $options = [],
    ): InferenceResult|VideoInferenceResult;

    /**
     * @param  array<string, mixed>  $options
     */
    public function annotate(
        Provider $providerType,
        string $mediaPath,
        MediaType $mediaType,
        string $model,
        Device $device,
        array $options = [],
    ): AnnotatedResult;
}
