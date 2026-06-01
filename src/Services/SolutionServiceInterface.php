<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\UltralyticsSolution;
use B7s\FluentVision\Results\SolutionResult;

interface SolutionServiceInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function run(
        UltralyticsSolution $solution,
        string $source,
        string $model,
        Device $device,
        array $options = [],
    ): SolutionResult;
}
