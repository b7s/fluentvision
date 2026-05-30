<?php

declare(strict_types=1);

namespace B7s\FluentVision\Support;

use function is_array;
use function is_string;

class ArrayNarrower
{
    /**
     * @param  array<mixed, mixed>  $data
     * @return array<string, mixed>
     */
    public static function narrowToStringKeys(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public static function narrowToArrayOfAssoc(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $result[] = self::narrowToStringKeys($item);
            }
        }

        return $result;
    }
}
