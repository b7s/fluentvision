<?php

declare(strict_types=1);

use B7s\FluentVision\Config;
use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Services\InferenceServiceInterface;
use B7s\FluentVision\Services\ModelServiceInterface;

describe('SavePath', function () {
    it('resolves absolute savePath from fluent method', function () {
        $fv = FluentVision::make();
        $fv->savePath('/tmp/custom-output');

        expect($fv->getSavePath())->toBe('/tmp/custom-output');
    });

    it('resolves relative savePath from fluent method against cwd', function () {
        $fv = FluentVision::make();
        $fv->savePath('relative-output');

        expect($fv->getSavePath())->toBe(getcwd().'/relative-output');
    });

    it('falls back to config savePath when fluent method not called', function () {
        $fv = FluentVision::make(__DIR__.'/../fixtures/test-config.php');

        expect($fv->getSavePath())->toBe('/tmp/fluentvision-test-output');
    });

    it('fluent savePath overrides config savePath', function () {
        $fv = FluentVision::make(__DIR__.'/../fixtures/test-config.php');
        $fv->savePath('/override/path');

        expect($fv->getSavePath())->toBe('/override/path');
    });

    it('creates output directory when it does not exist', function () {
        $tmpDir = sys_get_temp_dir().'/fluentvision-savepath-test-'.uniqid('', true);
        $config = new Config('/nonexistent/path');

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $inferenceService->shouldReceive('annotate')
            ->once()
            ->andReturn(new AnnotatedResult(
                imagePath: '/tmp/test.jpg',
                annotatedPath: $tmpDir.'/test_annotated.jpg',
                provider: 'ultralytics',
                model: 'yolo26s.pt',
                detectionCount: 1,
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
                ->annotate();

            expect(is_dir($tmpDir))->toBeTrue();
        } finally {
            @rmdir($tmpDir);
            Mockery::close();
        }
    });

    it('throws when output directory cannot be created', function () {
        $config = new Config('/nonexistent/path');
        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')
            ->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $fv = new FluentVision(
            configPath: '/nonexistent/path',
            config: $config,
            inferenceService: $inferenceService,
            modelService: $modelService,
        );

        $fv->savePath('/proc/impossible-dir')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->media('/tmp/test.jpg');

        $thrown = false;
        try {
            @$fv->annotate();
        } catch (RuntimeException) {
            $thrown = true;
        }
        Mockery::close();

        expect($thrown)->toBeTrue();
    });

    it('passes save_path option to inference service', function () {
        $tmpDir = sys_get_temp_dir().'/fluentvision-savepath-opt-'.uniqid('', true);
        $config = new Config('/nonexistent/path');

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $inferenceService->shouldReceive('annotate')
            ->once()
            ->withArgs(function (Provider $p, string $path, MediaType $type, string $m, Device $d, array $opts) use ($tmpDir) {
                return $opts['save'] === true && $opts['save_path'] === $tmpDir;
            })
            ->andReturn(new AnnotatedResult(
                imagePath: '/tmp/test.jpg',
                annotatedPath: $tmpDir.'/test_annotated.jpg',
                provider: 'ultralytics',
                model: 'yolo26s.pt',
                detectionCount: 1,
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
                ->annotate();

            expect(true)->toBeTrue();
        } finally {
            @rmdir($tmpDir);
            Mockery::close();
        }
    });
});
