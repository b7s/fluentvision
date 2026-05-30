<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

enum NanodetModel: string
{
    use HasEnumOptions;

    case PlusM320 = 'nanodet-plus-m-320';
    case PlusM416 = 'nanodet-plus-m-416';
    case PlusM1x5 = 'nanodet-plus-m-1.5x';
    case PlusT416 = 'nanodet-plus-t-416';
    case G416 = 'nanodet-g-416';
    case EfficientLite320 = 'nanodet-efficientlite-320';
    case RepVGGA416 = 'nanodet-repvGG-A416';

    public function label(): string
    {
        return match ($this) {
            self::PlusM320 => 'NanoDet-Plus M 320',
            self::PlusM416 => 'NanoDet-Plus M 416',
            self::PlusM1x5 => 'NanoDet-Plus M 1.5x',
            self::PlusT416 => 'NanoDet-Plus T 416',
            self::G416 => 'NanoDet-G 416',
            self::EfficientLite320 => 'NanoDet EfficientLite 320',
            self::RepVGGA416 => 'NanoDet RepVGG-A 416',
        };
    }

    public function dirname(): string
    {
        return $this->value;
    }

    public function configFilename(): string
    {
        return $this->value.'.yml';
    }

    public function checkpointFilename(): string
    {
        return $this->value.'.ckpt';
    }

    public function configUrl(): string
    {
        return 'https://raw.githubusercontent.com/RangiLyu/nanodet/main/config/'.$this->configFilename();
    }

    public function checkpointUrl(): string
    {
        return 'https://github.com/RangiLyu/nanodet/releases/download/v1.0.0/'.$this->checkpointFilename();
    }

    public static function repoCloneUrl(): string
    {
        return 'https://github.com/RangiLyu/nanodet.git';
    }
}
