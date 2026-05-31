<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

class StreamResult
{
    /** @var array<InferenceResult> */
    private array $frames = [];

    private float $totalTime = 0.0;

    private bool $stopped = false;

    private ?string $streamUrl = null;

    private bool $running = false;

    private StopSignal $stopSignal;

    /** @var (\Closure(): void)|null */
    private $killCallback = null;

    public function __construct(
        public string $source,
        public string $provider,
        public string $model,
    ) {
        $this->stopSignal = new StopSignal;
    }

    public function addFrame(InferenceResult $frame): void
    {
        $this->frames[] = $frame;
    }

    public function setTotalTime(float $time): void
    {
        $this->totalTime = $time;
    }

    public function setStopped(bool $stopped): void
    {
        $this->stopped = $stopped;
    }

    public function setStreamUrl(?string $url): void
    {
        $this->streamUrl = $url;
    }

    public function setRunning(bool $running): void
    {
        $this->running = $running;
    }

    public function setKillCallback(\Closure $callback): void
    {
        $this->killCallback = $callback;
    }

    public function stopStream(): void
    {
        $this->stopSignal->requestStop();

        if ($this->killCallback !== null) {
            ($this->killCallback)();
        }
    }

    public function isStopRequested(): bool
    {
        return $this->stopSignal->isStopRequested();
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * @return array<InferenceResult>
     */
    public function getFrames(): array
    {
        return $this->frames;
    }

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

    public function getTotalTime(): float
    {
        return $this->totalTime;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function getStreamUrl(): ?string
    {
        return $this->streamUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'source' => $this->source,
            'provider' => $this->provider,
            'model' => $this->model,
            'frame_count' => $this->getFrameCount(),
            'total_detections' => $this->getTotalDetections(),
            'total_time' => $this->totalTime,
            'average_inference_time' => $this->getAverageInferenceTime(),
            'stopped' => $this->stopped,
            'running' => $this->running,
            'frames' => array_map(
                static fn (InferenceResult $frame): array => $frame->toArray(),
                $this->frames,
            ),
        ];

        if ($this->streamUrl !== null) {
            $array['stream_url'] = $this->streamUrl;
        }

        return $array;
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
        $streamUrl = $data['stream_url'] ?? null;

        $result = new self(
            source: is_string($source) ? $source : '',
            provider: is_string($provider) ? $provider : '',
            model: is_string($model) ? $model : '',
        );

        foreach ($frames as $frame) {
            $result->addFrame($frame);
        }

        $result->setTotalTime(is_float($totalTime) || is_int($totalTime) ? (float) $totalTime : 0.0);
        $result->setStopped(is_bool($stopped) ? $stopped : false);
        $result->setStreamUrl(is_string($streamUrl) ? $streamUrl : null);

        return $result;
    }
}
