<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

readonly class AnnotatedResult
{
    public function __construct(
        public string $imagePath,
        public string $annotatedPath,
        public string $provider,
        public string $model,
        public int $detectionCount,
    ) {}

    public function hasAnnotatedImage(): bool
    {
        return $this->annotatedPath !== '' && file_exists($this->annotatedPath);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'image_path' => $this->imagePath,
            'annotated_path' => $this->annotatedPath,
            'provider' => $this->provider,
            'model' => $this->model,
            'detection_count' => $this->detectionCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $imagePath = $data['image_path'] ?? '';
        $annotatedPath = $data['annotated_path'] ?? '';
        $provider = $data['provider'] ?? '';
        $model = $data['model'] ?? '';
        $detectionCount = $data['detection_count'] ?? 0;

        return new self(
            imagePath: is_string($imagePath) ? $imagePath : '',
            annotatedPath: is_string($annotatedPath) ? $annotatedPath : '',
            provider: is_string($provider) ? $provider : '',
            model: is_string($model) ? $model : '',
            detectionCount: is_int($detectionCount) ? $detectionCount : 0,
        );
    }
}
