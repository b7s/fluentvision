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
    case RepVGGA416 = 'nanodet-repvgg-a-416';

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
        return $this->repoConfigPath();
    }

    public function checkpointFilename(): string
    {
        return $this->repoCheckpointName();
    }

    public function repoConfigPath(): string
    {
        return match ($this) {
            self::PlusM320 => 'config/nanodet-plus-m_320.yml',
            self::PlusM416 => 'config/nanodet-plus-m_416.yml',
            self::PlusM1x5 => 'config/nanodet-plus-m-1.5x_416.yml',
            self::PlusT416 => 'config/legacy_v0.x_configs/nanodet-m-416.yml',
            self::G416 => 'config/legacy_v0.x_configs/nanodet-g.yml',
            self::EfficientLite320 => 'config/legacy_v0.x_configs/EfficientNet-Lite/nanodet-EfficientNet-Lite0_320.yml',
            self::RepVGGA416 => 'config/legacy_v0.x_configs/RepVGG/nanodet-RepVGG-A0_416.yml',
        };
    }

    public function repoCheckpointName(): string
    {
        return match ($this) {
            self::PlusM320 => 'nanodet-plus-m_320_checkpoint.ckpt',
            self::PlusM416 => 'nanodet-plus-m_416_checkpoint.ckpt',
            self::PlusM1x5 => 'nanodet-plus-m-1.5x_416_checkpoint.ckpt',
            self::PlusT416 => 'nanodet-m_416_checkpoint.ckpt',
            self::G416 => 'nanodet-g_checkpoint.ckpt',
            self::EfficientLite320 => 'nanodet-EfficientNet-Lite0_320_checkpoint.ckpt',
            self::RepVGGA416 => 'nanodet-RepVGG-A0_416_checkpoint.ckpt',
        };
    }

    public function checkpointUrl(): string
    {
        return 'https://github.com/RangiLyu/nanodet/releases/download/v1.0.0-alpha-1/'.$this->repoCheckpointName();
    }

    public static function repoCloneUrl(): string
    {
        return 'https://github.com/RangiLyu/nanodet.git';
    }
}
