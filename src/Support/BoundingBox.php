<?php

declare(strict_types=1);

namespace B7s\FluentVision\Support;

readonly class BoundingBox
{
    public function __construct(
        public float $x1,
        public float $y1,
        public float $x2,
        public float $y2,
    ) {}

    public function width(): float
    {
        return $this->x2 - $this->x1;
    }

    public function height(): float
    {
        return $this->y2 - $this->y1;
    }

    public function area(): float
    {
        return $this->width() * $this->height();
    }

    public function centerX(): float
    {
        return $this->x1 + ($this->width() / 2);
    }

    public function centerY(): float
    {
        return $this->y1 + ($this->height() / 2);
    }

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'x1' => $this->x1,
            'y1' => $this->y1,
            'x2' => $this->x2,
            'y2' => $this->y2,
            'width' => $this->width(),
            'height' => $this->height(),
            'area' => $this->area(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $x1 = $data['x1'] ?? 0;
        $y1 = $data['y1'] ?? 0;
        $x2 = $data['x2'] ?? 0;
        $y2 = $data['y2'] ?? 0;

        return new self(
            x1: is_float($x1) || is_int($x1) ? (float) $x1 : 0.0,
            y1: is_float($y1) || is_int($y1) ? (float) $y1 : 0.0,
            x2: is_float($x2) || is_int($x2) ? (float) $x2 : 0.0,
            y2: is_float($y2) || is_int($y2) ? (float) $y2 : 0.0,
        );
    }
}
