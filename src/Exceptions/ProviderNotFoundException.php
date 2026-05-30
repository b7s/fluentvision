<?php

declare(strict_types=1);

namespace B7s\FluentVision\Exceptions;

class ProviderNotFoundException extends FluentVisionException
{
    public static function fromName(string $name): self
    {
        return new self(
            sprintf('Provider "%s" not found. Available providers: ultralytics, nanodet', $name),
        );
    }
}
