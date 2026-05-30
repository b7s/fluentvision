<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

use function array_column;
use function array_combine;
use function array_map;

trait HasEnumOptions
{
    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $cases = self::cases();

        return array_combine(
            array_map(static fn (self $c): string => $c->value, $cases),
            array_map(static fn (self $c): string => $c->label(), $cases),
        );
    }
}
