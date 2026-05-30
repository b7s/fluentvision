<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

readonly class VideoInferenceResult
{
    /**
     * @param  array<InferenceResult>  $frames
     */
    public function __construct(
        public string $videoPath,
        public string $provider,
        public string $model,
        public array $frames,
        public float $totalInferenceTime,
    ) {}

    public function getFrameCount(): int
    {
        return count($this->frames);
    }

    public function getTotalDetections(): int
    {
        return array_sum(
            array_map(
                static fn (InferenceResult $f): int => $f->getDetectionCount(),
                $this->frames,
            ),
        );
    }

    public function getAverageInferenceTime(): float
    {
        if ($this->frames === []) {
            return 0.0;
        }

        return $this->totalInferenceTime / count($this->frames);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'video_path' => $this->videoPath,
            'provider' => $this->provider,
            'model' => $this->model,
            'frame_count' => $this->getFrameCount(),
            'total_detections' => $this->getTotalDetections(),
            'total_inference_time' => $this->totalInferenceTime,
            'average_inference_time' => $this->getAverageInferenceTime(),
            'frames' => array_map(
                static fn (InferenceResult $f): array => $f->toArray(),
                $this->frames,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<InferenceResult>  $frames
     */
    public static function fromArray(array $data, array $frames = []): self
    {
        $videoPath = $data['video_path'] ?? '';
        $provider = $data['provider'] ?? '';
        $model = $data['model'] ?? '';
        $totalInferenceTime = $data['total_inference_time'] ?? 0;

        return new self(
            videoPath: is_string($videoPath) ? $videoPath : '',
            provider: is_string($provider) ? $provider : '',
            model: is_string($model) ? $model : '',
            frames: $frames,
            totalInferenceTime: is_float($totalInferenceTime) || is_int($totalInferenceTime) ? (float) $totalInferenceTime : 0.0,
        );
    }
}
