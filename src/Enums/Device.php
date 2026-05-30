<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

enum Device: string
{
    use HasEnumOptions;

    case Cpu = 'cpu';
    case Gpu = 'gpu';

    public function label(): string
    {
        return match ($this) {
            self::Cpu => 'CPU',
            self::Gpu => 'GPU',
        };
    }

    public function isCpu(): bool
    {
        return $this === self::Cpu;
    }

    public function isGpu(): bool
    {
        return $this === self::Gpu;
    }

    public function toUltralyticsArg(): string
    {
        return match ($this) {
            self::Cpu => 'cpu',
            self::Gpu => '0',
        };
    }

    public function toNanodetArg(): string
    {
        return match ($this) {
            self::Cpu => 'cpu',
            self::Gpu => 'cuda:0',
        };
    }
}
