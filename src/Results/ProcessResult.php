<?php

declare(strict_types=1);

namespace B7s\FluentVision\Results;

use B7s\FluentVision\Support\ArrayNarrower;

readonly class ProcessResult
{
    public function __construct(
        public InferenceResult|VideoInferenceResult $detections,
        public AnnotatedResult $annotation,
    ) {}

    public function hasAnnotatedImage(): bool
    {
        return $this->annotation->hasAnnotatedImage();
    }

    public function getDetectionCount(): int
    {
        if ($this->detections instanceof VideoInferenceResult) {
            return $this->detections->getTotalDetections();
        }

        return $this->detections->getDetectionCount();
    }

    public function getAnnotatedPath(): string
    {
        return $this->annotation->annotatedPath;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'detections' => $this->detections->toArray(),
            'annotation' => $this->annotation->toArray(),
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $flags);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $detectionsData = $data['detections'] ?? [];
        $annotationData = $data['annotation'] ?? [];

        if (! is_array($detectionsData)) {
            $detectionsData = [];
        }

        if (! is_array($annotationData)) {
            $annotationData = [];
        }

        $detections = InferenceResult::fromArray(
            ArrayNarrower::narrowToStringKeys($detectionsData),
        );

        $annotation = AnnotatedResult::fromArray(
            ArrayNarrower::narrowToStringKeys($annotationData),
        );

        return new self(
            detections: $detections,
            annotation: $annotation,
        );
    }
}
