<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

use B7s\FluentVision\Support\BoundingBox;

readonly class DetectionResult
{
    public function __construct(
        public string $class,
        public float $confidence,
        public BoundingBox $box,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'confidence' => $this->confidence,
            'box' => $this->box->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $class = $data['class'] ?? '';
        $confidence = $data['confidence'] ?? 0;
        $boxData = $data['box'] ?? [];

        return new self(
            class: is_string($class) ? $class : '',
            confidence: is_float($confidence) || is_int($confidence) ? (float) $confidence : 0.0,
            box: BoundingBox::fromArray(is_array($boxData) ? self::narrowBoxData($boxData) : []),
        );
    }

    /**
     * @param  array<mixed>  $boxData
     * @return array<string, mixed>
     */
    private static function narrowBoxData(array $boxData): array
    {
        $result = [];
        foreach ($boxData as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
