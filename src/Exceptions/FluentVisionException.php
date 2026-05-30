<?php

declare(strict_types=1);

namespace B7s\FluentVision\Exceptions;

class FluentVisionException extends \RuntimeException
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
