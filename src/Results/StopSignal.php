<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

class StopSignal
{
    private bool $stopped = false;

    public function requestStop(): void
    {
        $this->stopped = true;
    }

    public function isStopRequested(): bool
    {
        return $this->stopped;
    }
}
