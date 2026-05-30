<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

use const PATHINFO_EXTENSION;

use function pathinfo;

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

    public static function inferFromModel(string $modelPath): ?self
    {
        $ext = pathinfo($modelPath, PATHINFO_EXTENSION);

        if (in_array($ext, self::ultralyticsExtensions(), true)) {
            return self::Ultralytics;
        }

        if (in_array($ext, self::nanodetExtensions(), true)) {
            return self::Nanodet;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function ultralyticsExtensions(): array
    {
        return ['pt', 'onnx', 'engine', 'trt', 'mlmodel', 'mlpackage', 'tflite', 'pb', 'h5', 'savedmodel'];
    }

    /**
     * @return array<int, string>
     */
    public static function nanodetExtensions(): array
    {
        return ['ckpt'];
    }
}
