<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

readonly class InferenceResult
{
    /**
     * @param  array<DetectionResult>  $detections
     */
    public function __construct(
        public string $imagePath,
        public string $provider,
        public string $model,
        public array $detections,
        public float $inferenceTime,
    ) {}

    public function getDetectionCount(): int
    {
        return count($this->detections);
    }

    public function isEmpty(): bool
    {
        return $this->detections === [];
    }

    public function isNotEmpty(): bool
    {
        return $this->detections !== [];
    }

    /**
     * @return array<string>
     */
    public function getClasses(): array
    {
        $classes = array_map(
            static fn (DetectionResult $d): string => $d->class,
            $this->detections,
        );

        return array_values(array_unique($classes));
    }

    public function filterByClass(string $class): self
    {
        $filtered = array_filter(
            $this->detections,
            static fn (DetectionResult $d): bool => $d->class === $class,
        );

        return new self(
            imagePath: $this->imagePath,
            provider: $this->provider,
            model: $this->model,
            detections: array_values($filtered),
            inferenceTime: $this->inferenceTime,
        );
    }

    public function filterByMinConfidence(float $minConfidence): self
    {
        $filtered = array_filter(
            $this->detections,
            static fn (DetectionResult $d): bool => $d->confidence >= $minConfidence,
        );

        return new self(
            imagePath: $this->imagePath,
            provider: $this->provider,
            model: $this->model,
            detections: array_values($filtered),
            inferenceTime: $this->inferenceTime,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'image_path' => $this->imagePath,
            'provider' => $this->provider,
            'model' => $this->model,
            'detection_count' => $this->getDetectionCount(),
            'inference_time' => $this->inferenceTime,
            'detections' => array_map(
                static fn (DetectionResult $d): array => $d->toArray(),
                $this->detections,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<DetectionResult>  $detections
     */
    public static function fromArray(array $data, array $detections = []): self
    {
        $imagePath = $data['image_path'] ?? '';
        $provider = $data['provider'] ?? '';
        $model = $data['model'] ?? '';
        $inferenceTime = $data['inference_time'] ?? 0;

        return new self(
            imagePath: is_string($imagePath) ? $imagePath : '',
            provider: is_string($provider) ? $provider : '',
            model: is_string($model) ? $model : '',
            detections: $detections,
            inferenceTime: is_float($inferenceTime) || is_int($inferenceTime) ? (float) $inferenceTime : 0.0,
        );
    }
}
