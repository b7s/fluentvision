<?php

declare(strict_types=1);

namespace B7s\FluentVision\Exceptions;

class PythonNotFoundException extends FluentVisionException
{
    /**
     * @param  array<string>  $paths
     */
    public static function fromPaths(array $paths): self
    {
        return new self(
            sprintf('Python interpreter not found. Searched paths: %s', implode(', ', $paths)),
        );
    }
}
