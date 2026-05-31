<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

use const PATHINFO_EXTENSION;

use function in_array;
use function pathinfo;
use function str_starts_with;

enum MediaType: string
{
    use HasEnumOptions;

    case Image = 'image';
    case Video = 'video';
    case Stream = 'stream';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Video => 'Video',
            self::Stream => 'Stream',
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

    public function isStream(): bool
    {
        return $this === self::Stream;
    }

    public static function inferFromPath(string $path): self
    {
        if (self::isStreamSource($path)) {
            return self::Stream;
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        if (in_array($ext, self::videoExtensions(), true)) {
            return self::Video;
        }

        return self::Image;
    }

    public static function isStreamSource(string $source): bool
    {
        return $source === '0'
        || str_starts_with($source, 'rtsp://')
        || str_starts_with($source, 'rtmp://')
        || str_starts_with($source, 'tcp://')
        || str_starts_with($source, 'udp://')
        || str_starts_with($source, 'http://')
        || str_starts_with($source, 'https://')
        || preg_match('/^\d+$/', $source) === 1;
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
