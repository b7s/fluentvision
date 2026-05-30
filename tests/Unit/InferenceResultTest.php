<?php

declare(strict_types=1);

use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Support\BoundingBox;

describe('InferenceResult', function () {
    it('creates with detections', function () {
        $detections = [
            new DetectionResult(class: 'person', confidence: 0.95, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
            new DetectionResult(class: 'car', confidence: 0.80, box: new BoundingBox(x1: 150, y1: 50, x2: 300, y2: 200)),
        ];

        $result = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: $detections,
            inferenceTime: 0.123,
        );

        expect($result->imagePath)->toBe('/tmp/test.jpg');
        expect($result->provider)->toBe('ultralytics');
        expect($result->model)->toBe('yolo26s.pt');
        expect($result->inferenceTime)->toBe(0.123);
    });

    it('counts detections', function () {
        $detections = [
            new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
            new DetectionResult(class: 'car', confidence: 0.8, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
            new DetectionResult(class: 'person', confidence: 0.7, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
        ];

        $result = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: $detections,
            inferenceTime: 0.1,
        );

        expect($result->getDetectionCount())->toBe(3);
    });

    it('checks if empty', function () {
        $empty = new InferenceResult(imagePath: '/tmp/test.jpg', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.1);
        $notEmpty = new InferenceResult(imagePath: '/tmp/test.jpg', provider: 'ultralytics', model: 'yolo26s.pt', detections: [new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1))], inferenceTime: 0.1);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($notEmpty->isEmpty())->toBeFalse();
        expect($notEmpty->isNotEmpty())->toBeTrue();
    });

    it('returns unique classes', function () {
        $detections = [
            new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1)),
            new DetectionResult(class: 'car', confidence: 0.8, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1)),
            new DetectionResult(class: 'person', confidence: 0.7, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1)),
        ];

        $result = new InferenceResult(imagePath: '/tmp/test.jpg', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections, inferenceTime: 0.1);

        expect($result->getClasses())->toBe(['person', 'car']);
    });

    it('filters by class', function () {
        $detections = [
            new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1)),
            new DetectionResult(class: 'car', confidence: 0.8, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1)),
        ];

        $result = new InferenceResult(imagePath: '/tmp/test.jpg', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections, inferenceTime: 0.1);
        $filtered = $result->filterByClass('person');

        expect($filtered->getDetectionCount())->toBe(1);
        expect($filtered->detections[0]->class)->toBe('person');
    });

    it('filters by min confidence', function () {
        $detections = [
            new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1)),
            new DetectionResult(class: 'car', confidence: 0.3, box: new BoundingBox(x1: 0, y1: 0, x2: 1, y2: 1)),
        ];

        $result = new InferenceResult(imagePath: '/tmp/test.jpg', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections, inferenceTime: 0.1);
        $filtered = $result->filterByMinConfidence(0.5);

        expect($filtered->getDetectionCount())->toBe(1);
        expect($filtered->detections[0]->class)->toBe('person');
    });

    it('converts to array', function () {
        $result = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: [],
            inferenceTime: 0.1,
        );

        $array = $result->toArray();

        expect($array['image_path'])->toBe('/tmp/test.jpg');
        expect($array['provider'])->toBe('ultralytics');
        expect($array['detection_count'])->toBe(0);
    });

    it('creates from array', function () {
        $result = InferenceResult::fromArray([
            'image_path' => '/tmp/test.jpg',
            'provider' => 'ultralytics',
            'model' => 'yolo26s.pt',
            'inference_time' => 0.1,
        ], []);

        expect($result->imagePath)->toBe('/tmp/test.jpg');
        expect($result->getDetectionCount())->toBe(0);
    });
});
