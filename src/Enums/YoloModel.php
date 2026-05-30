<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

enum YoloModel: string
{
    use HasEnumOptions;

    case YOLO26n = 'yolo26n.pt';
    case YOLO26s = 'yolo26s.pt';
    case YOLO26m = 'yolo26m.pt';
    case YOLO26l = 'yolo26l.pt';
    case YOLO26x = 'yolo26x.pt';

    public function label(): string
    {
        return match ($this) {
            self::YOLO26n => 'YOLO26 Nano',
            self::YOLO26s => 'YOLO26 Small',
            self::YOLO26m => 'YOLO26 Medium',
            self::YOLO26l => 'YOLO26 Large',
            self::YOLO26x => 'YOLO26 Extra Large',
        };
    }

    public function filename(): string
    {
        return $this->value;
    }

    public function downloadUrl(): string
    {
        return 'https://github.com/ultralytics/assets/releases/download/v8.4.0/'.$this->value;
    }
}
