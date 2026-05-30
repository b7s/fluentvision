<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

enum Provider: string
{
    use HasEnumOptions;

    case Ultralytics = 'ultralytics';
    case Nanodet = 'nanodet';

    public function label(): string
    {
        return match ($this) {
            self::Ultralytics => 'Ultralytics YOLO26',
            self::Nanodet => 'NanoDet-Plus',
        };
    }

    public function isUltralytics(): bool
    {
        return $this === self::Ultralytics;
    }

    public function isNanodet(): bool
    {
        return $this === self::Nanodet;
    }
}
