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

    case YOLOE26s = 'yoloe-26s-seg.pt';
    case YOLOE26m = 'yoloe-26m-seg.pt';
    case YOLOE26l = 'yoloe-26l-seg.pt';
    case YOLOE26sPF = 'yoloe-26s-seg-pf.pt';
    case YOLOE26mPF = 'yoloe-26m-seg-pf.pt';
    case YOLOE26lPF = 'yoloe-26l-seg-pf.pt';

    public function label(): string
    {
        return match ($this) {
            self::YOLO26n => 'YOLO26 Nano',
            self::YOLO26s => 'YOLO26 Small',
            self::YOLO26m => 'YOLO26 Medium',
            self::YOLO26l => 'YOLO26 Large',
            self::YOLO26x => 'YOLO26 Extra Large',
            self::YOLOE26s => 'YOLOE-26 Small Seg',
            self::YOLOE26m => 'YOLOE-26 Medium Seg',
            self::YOLOE26l => 'YOLOE-26 Large Seg',
            self::YOLOE26sPF => 'YOLOE-26 Small Seg (Prompt-Free)',
            self::YOLOE26mPF => 'YOLOE-26 Medium Seg (Prompt-Free)',
            self::YOLOE26lPF => 'YOLOE-26 Large Seg (Prompt-Free)',
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

    public function isYoloe(): bool
    {
        return $this === self::YOLOE26s
            || $this === self::YOLOE26m
            || $this === self::YOLOE26l
            || $this === self::YOLOE26sPF
            || $this === self::YOLOE26mPF
            || $this === self::YOLOE26lPF;
    }

    public function isPromptFree(): bool
    {
        return $this === self::YOLOE26sPF
            || $this === self::YOLOE26mPF
            || $this === self::YOLOE26lPF;
    }

    public function supportsPrompts(): bool
    {
        return $this->isYoloe() && ! $this->isPromptFree();
    }
}
