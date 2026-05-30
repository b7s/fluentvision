<?php

declare(strict_types=1);

namespace B7s\FluentVision\Exceptions;

class InferenceException extends FluentVisionException
{
    public static function fromProcessError(string $script, string $error): self
    {
        return new self(
            sprintf('Inference failed for script "%s": %s', $script, $error),
        );
    }

    public static function fromInvalidOutput(string $output): self
    {
        return new self(
            sprintf('Invalid inference output: %s', $output),
        );
    }

    public static function fromTimeout(string $script, int $timeout): self
    {
        return new self(
            sprintf('Inference timed out for script "%s" after %d seconds', $script, $timeout),
        );
    }
}
