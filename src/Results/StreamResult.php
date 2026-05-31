<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

readonly class StreamResult
{
    /**
     * @param  array<InferenceResult>  $frames
     */
    public function __construct(
        public string $source,
        public string $provider,
        public string $model,
        public array $frames,
        public float $totalTime,
        public bool $stopped,
    ) {}

    public function getFrameCount(): int
    {
        return count($this->frames);
    }

    public function getTotalDetections(): int
    {
        $total = 0;
        foreach ($this->frames as $frame) {
            $total += $frame->getDetectionCount();
        }

        return $total;
    }

    public function getAverageInferenceTime(): float
    {
        $count = $this->getFrameCount();
        if ($count === 0) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->frames as $frame) {
            $total += $frame->inferenceTime;
        }

        return $total / $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'provider' => $this->provider,
            'model' => $this->model,
            'frame_count' => $this->getFrameCount(),
            'total_detections' => $this->getTotalDetections(),
            'total_time' => $this->totalTime,
            'average_inference_time' => $this->getAverageInferenceTime(),
            'stopped' => $this->stopped,
            'frames' => array_map(
                static fn (InferenceResult $frame): array => $frame->toArray(),
                $this->frames,
            ),
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $flags);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<InferenceResult>  $frames
     */
    public static function fromArray(array $data, array $frames = []): self
    {
        $source = $data['source'] ?? '';
        $provider = $data['provider'] ?? '';
        $model = $data['model'] ?? '';
        $totalTime = $data['total_time'] ?? 0;
        $stopped = $data['stopped'] ?? false;

        return new self(
            source: is_string($source) ? $source : '',
            provider: is_string($provider) ? $provider : '',
            model: is_string($model) ? $model : '',
            frames: $frames,
            totalTime: is_float($totalTime) || is_int($totalTime) ? (float) $totalTime : 0.0,
            stopped: is_bool($stopped) ? $stopped : false,
        );
    }
}
