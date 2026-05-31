<?php

declare(strict_types=1);

use B7s\FluentVision\Config;
use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\ProcessResult;
use B7s\FluentVision\Services\InferenceServiceInterface;
use B7s\FluentVision\Services\ModelServiceInterface;
use B7s\FluentVision\Support\BoundingBox;

describe('Process', function () {
    it('withDetections chains fluently and defaults to true', function () {
        $fv = FluentVision::make();
        $result = $fv->withDetections();

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('withAnnotation chains fluently', function () {
        $fv = FluentVision::make();
        $result = $fv->withAnnotation();

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('withDetections can be disabled', function () {
        $fv = FluentVision::make();
        $result = $fv->withDetections(false);

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('withAnnotation can be disabled', function () {
        $fv = FluentVision::make();
        $result = $fv->withAnnotation(false);

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('process() calls detectAndAnnotate on inference service', function () {
        $tmpDir = sys_get_temp_dir().'/fluentvision-process-test-'.uniqid('', true);
        $config = new Config('/nonexistent/path');

        $inferenceResult = new InferenceResult(
            imagePath: '/tmp/test.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detections: [new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200))],
            inferenceTime: 0.123,
        );
        $annotatedResult = new AnnotatedResult(
            imagePath: '/tmp/test.jpg',
            annotatedPath: $tmpDir.'/test_annotated.jpg',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
            detectionCount: 1,
        );
        $processResult = new ProcessResult(
            detections: $inferenceResult,
            annotation: $annotatedResult,
        );

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $inferenceService->shouldReceive('detectAndAnnotate')
            ->once()
            ->andReturn($processResult);

        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')
            ->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $fv = new FluentVision(
            configPath: '/nonexistent/path',
            config: $config,
            inferenceService: $inferenceService,
            modelService: $modelService,
        );

        try {
            $result = $fv->savePath($tmpDir)
                ->model(YoloModel::YOLO26s)
                ->useCpu()
                ->withAnnotation()
                ->media('/tmp/test.jpg')
                ->process();

            expect($result)->toBeInstanceOf(ProcessResult::class)
                ->and($result->detections)->toBeInstanceOf(InferenceResult::class)
                ->and($result->annotation)->toBeInstanceOf(AnnotatedResult::class)
                ->and($result->getDetectionCount())->toBe(1)
                ->and($result->getAnnotatedPath())->toBe($tmpDir.'/test_annotated.jpg');
        } finally {
            @rmdir($tmpDir);
            Mockery::close();
        }
    });

    it('process() passes save=true when annotation enabled', function () {
        $tmpDir = sys_get_temp_dir().'/fluentvision-process-save-'.uniqid('', true);
        $config = new Config('/nonexistent/path');

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $inferenceService->shouldReceive('detectAndAnnotate')
            ->once()
            ->withArgs(function (Provider $p, string $path, MediaType $type, string $m, Device $d, array $opts) use ($tmpDir) {
                return $opts['save'] === true && $opts['save_path'] === $tmpDir;
            })
            ->andReturn(new ProcessResult(
                detections: new InferenceResult(
                    imagePath: '/tmp/test.jpg',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detections: [],
                    inferenceTime: 0.1,
                ),
                annotation: new AnnotatedResult(
                    imagePath: '/tmp/test.jpg',
                    annotatedPath: $tmpDir.'/test_annotated.jpg',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detectionCount: 0,
                ),
            ));

        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')
            ->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $fv = new FluentVision(
            configPath: '/nonexistent/path',
            config: $config,
            inferenceService: $inferenceService,
            modelService: $modelService,
        );

        try {
            $fv->savePath($tmpDir)
                ->model(YoloModel::YOLO26s)
                ->useCpu()
                ->withAnnotation()
                ->media('/tmp/test.jpg')
                ->process();

            expect(true)->toBeTrue();
        } finally {
            @rmdir($tmpDir);
            Mockery::close();
        }
    });

    it('process() passes save=false when annotation disabled', function () {
        $tmpDir = sys_get_temp_dir().'/fluentvision-process-nosave-'.uniqid('', true);
        $config = new Config('/nonexistent/path');

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $inferenceService->shouldReceive('detectAndAnnotate')
            ->once()
            ->withArgs(function (Provider $p, string $path, MediaType $type, string $m, Device $d, array $opts) {
                return $opts['save'] === false;
            })
            ->andReturn(new ProcessResult(
                detections: new InferenceResult(
                    imagePath: '/tmp/test.jpg',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detections: [],
                    inferenceTime: 0.1,
                ),
                annotation: new AnnotatedResult(
                    imagePath: '/tmp/test.jpg',
                    annotatedPath: '',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detectionCount: 0,
                ),
            ));

        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')
            ->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $fv = new FluentVision(
            configPath: '/nonexistent/path',
            config: $config,
            inferenceService: $inferenceService,
            modelService: $modelService,
        );

        try {
            $fv->savePath($tmpDir)
                ->model(YoloModel::YOLO26s)
                ->useCpu()
                ->withAnnotation(false)
                ->media('/tmp/test.jpg')
                ->process();

            expect(true)->toBeTrue();
        } finally {
            @rmdir($tmpDir);
            Mockery::close();
        }
    });

    it('process() throws when no media path set', function () {
        $fv = FluentVision::make();

        expect(fn () => $fv->process())->toThrow(RuntimeException::class);
    });

    it('process() throws when both withDetections and withAnnotation are disabled', function () {
        $fv = FluentVision::make()
            ->withDetections(false)
            ->withAnnotation(false)
            ->media('/tmp/test.jpg');

        expect(fn () => $fv->process())->toThrow(RuntimeException::class);
    });

    it('process() default withAnnotation=false passes save=false', function () {
        $tmpDir = sys_get_temp_dir().'/fluentvision-process-default-'.uniqid('', true);
        $config = new Config('/nonexistent/path');

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $inferenceService->shouldReceive('detectAndAnnotate')
            ->once()
            ->withArgs(function (Provider $p, string $path, MediaType $type, string $m, Device $d, array $opts) {
                return $opts['save'] === false;
            })
            ->andReturn(new ProcessResult(
                detections: new InferenceResult(
                    imagePath: '/tmp/test.jpg',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detections: [],
                    inferenceTime: 0.1,
                ),
                annotation: new AnnotatedResult(
                    imagePath: '/tmp/test.jpg',
                    annotatedPath: '',
                    provider: 'ultralytics',
                    model: 'yolo26s.pt',
                    detectionCount: 0,
                ),
            ));

        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')
            ->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $fv = new FluentVision(
            configPath: '/nonexistent/path',
            config: $config,
            inferenceService: $inferenceService,
            modelService: $modelService,
        );

        try {
            $fv->savePath($tmpDir)
                ->model(YoloModel::YOLO26s)
                ->useCpu()
                ->media('/tmp/test.jpg')
                ->process();

            expect(true)->toBeTrue();
        } finally {
            @rmdir($tmpDir);
            Mockery::close();
        }
    });
});
