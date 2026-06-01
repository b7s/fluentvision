<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

use function count;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

readonly class SolutionResult
{
    /**
     * @param  string  $solution  Solution name (e.g. 'count', 'heatmap')
     * @param  string  $source  Input source path
     * @param  string  $model  Model filename
     * @param  string  $provider  Provider name ('ultralytics')
     * @param  int  $frameCount  Number of frames processed
     * @param  float  $totalTime  Total processing time in seconds
     * @param  ?int  $totalTracks  Total tracked objects (most solutions)
     * @param  ?int  $inCount  Objects entering region (count, heatmap)
     * @param  ?int  $outCount  Objects exiting region (count, heatmap)
     * @param  array<string, int>  $classwiseCount  Per-class in/out counts (count, heatmap)
     * @param  ?array<int|string, mixed>  $workoutCount  Rep counts per track (workout)
     * @param  ?array<int|string, mixed>  $workoutAngle  Angles per track (workout)
     * @param  ?array<int|string, mixed>  $workoutStage  Stages per track (workout)
     * @param  array<string, float>  $speedDict  Per-track speeds (speed)
     * @param  array<string, int>  $regionCounts  Per-region counts (trackzone)
     * @param  array<int, array<string, mixed>>  $frames  Per-frame data (video)
     */
    public function __construct(
        public string $solution,
        public string $source,
        public string $model,
        public string $provider,
        public int $frameCount = 0,
        public float $totalTime = 0.0,
        public ?int $totalTracks = null,
        public ?int $inCount = null,
        public ?int $outCount = null,
        public array $classwiseCount = [],
        public ?int $queueCount = null,
        public ?int $totalCropObjects = null,
        public ?float $pixelsDistance = null,
        public ?array $workoutCount = null,
        public ?array $workoutAngle = null,
        public ?array $workoutStage = null,
        public ?int $filledSlots = null,
        public ?int $availableSlots = null,
        public array $speedDict = [],
        public ?bool $emailSent = null,
        public array $regionCounts = [],
        public ?string $annotatedPath = null,
        public array $frames = [],
    ) {}

    public function getFrameCount(): int
    {
        return $this->frameCount;
    }

    public function getTotalTracks(): ?int
    {
        return $this->totalTracks;
    }

    public function getInCount(): ?int
    {
        return $this->inCount;
    }

    public function getOutCount(): ?int
    {
        return $this->outCount;
    }

    /**
     * @return array<string, int>
     */
    public function getClasswiseCount(): array
    {
        return $this->classwiseCount;
    }

    public function getQueueCount(): ?int
    {
        return $this->queueCount;
    }

    public function getTotalCropObjects(): ?int
    {
        return $this->totalCropObjects;
    }

    public function getPixelsDistance(): ?float
    {
        return $this->pixelsDistance;
    }

    public function getFilledSlots(): ?int
    {
        return $this->filledSlots;
    }

    public function getAvailableSlots(): ?int
    {
        return $this->availableSlots;
    }

    /**
     * @return array<string, float>
     */
    public function getSpeedDict(): array
    {
        return $this->speedDict;
    }

    public function isEmailSent(): ?bool
    {
        return $this->emailSent;
    }

    public function hasAnnotation(): bool
    {
        return $this->annotatedPath !== null;
    }

    public function getAnnotatedPath(): ?string
    {
        return $this->annotatedPath;
    }

    public function getTotalTime(): float
    {
        return $this->totalTime;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'solution' => $this->solution,
            'source' => $this->source,
            'model' => $this->model,
            'provider' => $this->provider,
            'frame_count' => $this->frameCount,
            'total_time' => $this->totalTime,
        ];

        if ($this->totalTracks !== null) {
            $array['total_tracks'] = $this->totalTracks;
        }

        if ($this->inCount !== null) {
            $array['in_count'] = $this->inCount;
        }

        if ($this->outCount !== null) {
            $array['out_count'] = $this->outCount;
        }

        if ($this->classwiseCount !== []) {
            $array['classwise_count'] = $this->classwiseCount;
        }

        if ($this->queueCount !== null) {
            $array['queue_count'] = $this->queueCount;
        }

        if ($this->totalCropObjects !== null) {
            $array['total_crop_objects'] = $this->totalCropObjects;
        }

        if ($this->pixelsDistance !== null) {
            $array['pixels_distance'] = $this->pixelsDistance;
        }

        if ($this->workoutCount !== null) {
            $array['workout_count'] = $this->workoutCount;
        }

        if ($this->workoutAngle !== null) {
            $array['workout_angle'] = $this->workoutAngle;
        }

        if ($this->workoutStage !== null) {
            $array['workout_stage'] = $this->workoutStage;
        }

        if ($this->filledSlots !== null) {
            $array['filled_slots'] = $this->filledSlots;
        }

        if ($this->availableSlots !== null) {
            $array['available_slots'] = $this->availableSlots;
        }

        if ($this->speedDict !== []) {
            $array['speed_dict'] = $this->speedDict;
        }

        if ($this->emailSent !== null) {
            $array['email_sent'] = $this->emailSent;
        }

        if ($this->regionCounts !== []) {
            $array['region_counts'] = $this->regionCounts;
        }

        if ($this->annotatedPath !== null) {
            $array['annotated_path'] = $this->annotatedPath;
        }

        if ($this->frames !== []) {
            $array['frames'] = $this->frames;
        }

        return $array;
    }

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $narrowInt = static fn (mixed $v): ?int => is_int($v) ? $v : null;
        $narrowFloat = static fn (mixed $v): ?float => is_float($v) || is_int($v) ? (float) $v : null;
        $narrowBool = static fn (mixed $v): ?bool => is_bool($v) ? $v : null;
        $narrowString = static fn (mixed $v): ?string => is_string($v) ? $v : null;
        $narrowArray = static fn (mixed $v): array => is_array($v) ? $v : [];
        $narrowNullableArray = static fn (mixed $v): ?array => is_array($v) ? $v : null;

        $solution = $data['solution'] ?? '';
        $source = $data['source'] ?? '';
        $model = $data['model'] ?? '';
        $provider = $data['provider'] ?? '';

        $solution = is_string($solution) ? $solution : '';
        $source = is_string($source) ? $source : '';
        $model = is_string($model) ? $model : '';
        $provider = is_string($provider) ? $provider : '';

        $classwiseCount = $narrowArray($data['classwise_count'] ?? []);
        $speedDict = $narrowArray($data['speed_dict'] ?? []);
        $regionCounts = $narrowArray($data['region_counts'] ?? []);
        $frames = $narrowArray($data['frames'] ?? []);

        /** @var array<string, int> $classwiseCount */
        /** @var array<string, float> $speedDict */
        /** @var array<string, int> $regionCounts */
        /** @var array<int, array<string, mixed>> $frames */

        return new self(
            solution: $solution,
            source: $source,
            model: $model,
            provider: $provider,
            frameCount: $narrowInt($data['frame_count'] ?? 0) ?? 0,
            totalTime: $narrowFloat($data['total_time'] ?? $data['inference_time'] ?? 0) ?? 0.0,
            totalTracks: $narrowInt($data['total_tracks'] ?? null),
            inCount: $narrowInt($data['in_count'] ?? null),
            outCount: $narrowInt($data['out_count'] ?? null),
            classwiseCount: $classwiseCount,
            queueCount: $narrowInt($data['queue_count'] ?? null),
            totalCropObjects: $narrowInt($data['total_crop_objects'] ?? null),
            pixelsDistance: $narrowFloat($data['pixels_distance'] ?? null),
            workoutCount: $narrowNullableArray($data['workout_count'] ?? null),
            workoutAngle: $narrowNullableArray($data['workout_angle'] ?? null),
            workoutStage: $narrowNullableArray($data['workout_stage'] ?? null),
            filledSlots: $narrowInt($data['filled_slots'] ?? null),
            availableSlots: $narrowInt($data['available_slots'] ?? null),
            speedDict: $speedDict,
            emailSent: $narrowBool($data['email_sent'] ?? null),
            regionCounts: $regionCounts,
            annotatedPath: $narrowString($data['annotated_path'] ?? null),
            frames: $frames,
        );
    }
}
