<?php

declare(strict_types=1);

use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\ProcessResult;
use B7s\FluentVision\Results\VideoInferenceResult;
use B7s\FluentVision\Support\BoundingBox;

describe('ProcessResult', function () {
    it('creates with detections and annotation', function () {
        $detections = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: [new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200))],
            inferenceTime: 0.123,
        );
        $annotation = new AnnotatedResult(
            imagePath: '/tmp/test.jpg',
            annotatedPath: '/tmp/test_annotated.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detectionCount: 1,
        );

        $result = new ProcessResult(
            detections: $detections,
            annotation: $annotation,
        );

        expect($result->detections)->toBeInstanceOf(InferenceResult::class)
            ->and($result->annotation)->toBeInstanceOf(AnnotatedResult::class)
            ->and($result->getDetectionCount())->toBe(1)
            ->and($result->getAnnotatedPath())->toBe('/tmp/test_annotated.jpg');
    });

    it('checks annotated image existence', function () {
        $detections = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: [],
            inferenceTime: 0.1,
        );
        $annotation = new AnnotatedResult(
            imagePath: '/tmp/test.jpg',
            annotatedPath: '/nonexistent/annotated.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detectionCount: 0,
        );

        $result = new ProcessResult(
            detections: $detections,
            annotation: $annotation,
        );

        expect($result->hasAnnotatedImage())->toBeFalse();
    });

    it('accepts VideoInferenceResult as detections', function () {
        $videoDetections = new VideoInferenceResult(
            videoPath: '/tmp/clip.mp4',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            frames: [
                new InferenceResult(
                    imagePath: 'frame_0',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detections: [new DetectionResult(class: 'car', confidence: 0.8, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 100))],
                    inferenceTime: 0.05,
                ),
                new InferenceResult(
                    imagePath: 'frame_5',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detections: [
                        new DetectionResult(class: 'car', confidence: 0.7, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 100)),
                        new DetectionResult(class: 'person', confidence: 0.6, box: new BoundingBox(x1: 200, y1: 0, x2: 300, y2: 200)),
                    ],
                    inferenceTime: 0.06,
                ),
            ],
            totalInferenceTime: 0.11,
        );
        $annotation = new AnnotatedResult(
            imagePath: '/tmp/clip.mp4',
            annotatedPath: '',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detectionCount: 3,
        );

        $result = new ProcessResult(
            detections: $videoDetections,
            annotation: $annotation,
        );

        expect($result->detections)->toBeInstanceOf(VideoInferenceResult::class)
            ->and($result->getDetectionCount())->toBe(3);
    });

    it('converts to array', function () {
        $detections = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: [],
            inferenceTime: 0.1,
        );
        $annotation = new AnnotatedResult(
            imagePath: '/tmp/test.jpg',
            annotatedPath: '/tmp/test_annotated.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detectionCount: 0,
        );

        $result = new ProcessResult(
            detections: $detections,
            annotation: $annotation,
        );

        $array = $result->toArray();

        expect($array)->toHaveKey('detections')
            ->and($array)->toHaveKey('annotation')
            ->and($array['detections'])->toHaveKey('image_path')
            ->and($array['annotation'])->toHaveKey('annotated_path');
    });

    it('converts to json', function () {
        $detections = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: [],
            inferenceTime: 0.1,
        );
        $annotation = new AnnotatedResult(
            imagePath: '/tmp/test.jpg',
            annotatedPath: '/tmp/test_annotated.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detectionCount: 0,
        );

        $result = new ProcessResult(
            detections: $detections,
            annotation: $annotation,
        );

        $json = $result->toJson();

        expect(json_decode($json, true))->toHaveKey('detections')
            ->and(json_decode($json, true))->toHaveKey('annotation');
    });

    it('creates from array', function () {
        $data = [
            'detections' => [
                'image_path' => '/tmp/test.jpg',
                'provider' => 'ultralytics',
                'model' => 'yolo26s.pt',
                'inference_time' => 0.1,
            ],
            'annotation' => [
                'image_path' => '/tmp/test.jpg',
                'annotated_path' => '/tmp/test_annotated.jpg',
                'provider' => 'ultralytics',
                'model' => 'yolo26s.pt',
                'detection_count' => 2,
            ],
        ];

        $result = ProcessResult::fromArray($data);

        expect($result->detections)->toBeInstanceOf(InferenceResult::class)
            ->and($result->annotation)->toBeInstanceOf(AnnotatedResult::class)
            ->and($result->annotation->annotatedPath)->toBe('/tmp/test_annotated.jpg');
    });
});
