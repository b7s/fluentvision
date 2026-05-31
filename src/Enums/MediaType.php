<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

use const PATHINFO_EXTENSION;

use function in_array;
use function pathinfo;

enum MediaType: string
{
    use HasEnumOptions;

    case Image = 'image';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Video => 'Video',
        };
    }

    public function isImage(): bool
    {
        return $this === self::Image;
    }

    public function isVideo(): bool
    {
        return $this === self::Video;
    }

    public static function inferFromPath(string $path): self
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        if (in_array($ext, self::videoExtensions(), true)) {
            return self::Video;
        }

        return self::Image;
    }

    /**
     * @return array<int, string>
     */
    public static function videoExtensions(): array
    {
        return ['mp4', 'avi', 'mov', 'mkv', 'wmv', 'flv', 'webm', 'm4v', 'mpeg', 'mpg', '3gp', 'ts'];
    }

    /**
     * @return array<int, string>
     */
    public static function imageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif', 'ico', 'svg'];
    }
}
