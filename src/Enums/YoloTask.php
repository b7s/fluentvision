<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

enum YoloTask: string
{
    use HasEnumOptions;

    case Detect = 'detect';
    case Segment = 'segment';
    case Classify = 'classify';
    case Pose = 'pose';
    case Obb = 'obb';

    public function label(): string
    {
        return match ($this) {
            self::Detect => 'Object Detection',
            self::Segment => 'Segmentation',
            self::Classify => 'Classification',
            self::Pose => 'Pose Estimation',
            self::Obb => 'Oriented Bounding Box',
        };
    }

    public function modelSuffix(): string
    {
        return match ($this) {
            self::Detect => '',
            self::Segment => '-seg',
            self::Classify => '-cls',
            self::Pose => '-pose',
            self::Obb => '-obb',
        };
    }
}
