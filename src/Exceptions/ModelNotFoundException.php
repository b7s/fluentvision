<?php

declare(strict_types=1);

namespace B7s\FluentVision\Exceptions;

class ModelNotFoundException extends FluentVisionException
{
    public static function fromPath(string $path): self
    {
        return new self(
            sprintf('Model file not found at path: %s', $path),
        );
    }

    public static function fromName(string $name): self
    {
        return new self(
            sprintf('Model "%s" not found in model directory', $name),
        );
    }
}
