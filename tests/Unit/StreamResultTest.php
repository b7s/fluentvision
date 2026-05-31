<?php

declare(strict_types=1);

use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\StreamResult;
use B7s\FluentVision\Support\BoundingBox;

describe('StreamResult', function () {
    it('creates with all properties', function () {
        $frames = [
            new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.05),
            new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.06),
        ];

        $result = new StreamResult(
            source: 'rtsp://example.com/stream',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            frames: $frames,
            totalTime: 1.234,
            stopped: true,
        );

        expect($result->source)->toBe('rtsp://example.com/stream')
            ->and($result->provider)->toBe('ultralytics')
            ->and($result->model)->toBe('yolo26s.pt')
            ->and($result->totalTime)->toBe(1.234)
            ->and($result->stopped)->toBeTrue();
    });

    it('returns frame count', function () {
        $frames = [
            new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.05),
            new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.06),
            new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.04),
        ];

        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: $frames, totalTime: 1.0, stopped: false);

        expect($result->getFrameCount())->toBe(3);
    });

    it('returns zero frame count for empty frames', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: [], totalTime: 0.0, stopped: false);

        expect($result->getFrameCount())->toBe(0);
    });

    it('returns total detections across all frames', function () {
        $detections1 = [
            new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
            new DetectionResult(class: 'car', confidence: 0.8, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
        ];
        $detections2 = [
            new DetectionResult(class: 'person', confidence: 0.85, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
        ];

        $frames = [
            new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections1, inferenceTime: 0.05),
            new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections2, inferenceTime: 0.06),
        ];

        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: $frames, totalTime: 1.0, stopped: false);

        expect($result->getTotalDetections())->toBe(3);
    });

    it('returns zero total detections for empty frames', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: [], totalTime: 0.0, stopped: false);

        expect($result->getTotalDetections())->toBe(0);
    });

    it('returns average inference time', function () {
        $frames = [
            new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.10),
            new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.20),
        ];

        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: $frames, totalTime: 1.0, stopped: false);

        expect($result->getAverageInferenceTime())->toEqualWithDelta(0.15, 0.0001);
    });

    it('returns zero average inference time for empty frames', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: [], totalTime: 0.0, stopped: false);

        expect($result->getAverageInferenceTime())->toBe(0.0);
    });

    it('converts to array', function () {
        $detections = [new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200))];
        $frames = [
            new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections, inferenceTime: 0.05),
        ];

        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: $frames, totalTime: 1.5, stopped: true);
        $array = $result->toArray();

        expect($array['source'])->toBe('rtsp://test')
            ->and($array['provider'])->toBe('ultralytics')
            ->and($array['model'])->toBe('yolo26s.pt')
            ->and($array['frame_count'])->toBe(1)
            ->and($array['total_detections'])->toBe(1)
            ->and($array['total_time'])->toBe(1.5)
            ->and($array['average_inference_time'])->toBe(0.05)
            ->and($array['stopped'])->toBeTrue()
            ->and($array['frames'])->toHaveCount(1);
    });

    it('converts to json', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt', frames: [], totalTime: 0.0, stopped: false);
        $json = $result->toJson();

        expect($json)->toBeJson()
            ->and(json_decode($json, true)['source'])->toBe('rtsp://test');
    });

    it('creates from array', function () {
        $frames = [
            new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.05),
        ];

        $result = StreamResult::fromArray([
            'source' => 'rtsp://test',
            'provider' => 'ultralytics',
            'model' => 'yolo26s.pt',
            'total_time' => 1.5,
            'stopped' => true,
        ], $frames);

        expect($result->source)->toBe('rtsp://test')
            ->and($result->provider)->toBe('ultralytics')
            ->and($result->model)->toBe('yolo26s.pt')
            ->and($result->totalTime)->toBe(1.5)
            ->and($result->stopped)->toBeTrue()
            ->and($result->getFrameCount())->toBe(1);
    });

    it('creates from array with defaults for missing keys', function () {
        $result = StreamResult::fromArray([], []);

        expect($result->source)->toBe('')
            ->and($result->provider)->toBe('')
            ->and($result->model)->toBe('')
            ->and($result->totalTime)->toBe(0.0)
            ->and($result->stopped)->toBeFalse()
            ->and($result->getFrameCount())->toBe(0);
    });
});
