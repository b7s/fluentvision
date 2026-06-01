<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\UltralyticsSolution;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Results\SolutionResult;
use B7s\FluentVision\Results\StreamResult;
use B7s\FluentVision\Services\InferenceServiceInterface;
use B7s\FluentVision\Services\ModelServiceInterface;
use B7s\FluentVision\Services\SolutionServiceInterface;
use B7s\FluentVision\Services\StreamServiceInterface;

describe('FluentVision', function () {
    it('creates with make factory', function () {
        $fv = FluentVision::make();

        expect($fv)->toBeInstanceOf(FluentVision::class);
    });

    it('sets provider via enum', function () {
        $fv = FluentVision::make();
        $result = $fv->provider(Provider::Nanodet);

        expect($result)->toBeInstanceOf(FluentVision::class)
            ->and($fv->getProvider())->toBe(Provider::Nanodet);
    });

    it('sets provider via shorthand', function () {
        $fv = FluentVision::make();
        $fv->useUltralytics();
        expect($fv->getProvider())->toBe(Provider::Ultralytics);

        $fv->useNanodet();
        expect($fv->getProvider())->toBe(Provider::Nanodet);
    });

    it('sets model via YoloModel enum', function () {
        $fv = FluentVision::make();
        $fv->model(YoloModel::YOLO26s);

        expect($fv->getModel())->toBe('yolo26s.pt');
    });

    it('sets model via NanodetModel enum', function () {
        $fv = FluentVision::make();
        $fv->model(NanodetModel::PlusM416);

        expect($fv->getModel())->toBe('nanodet-plus-m-416');
    });

    it('sets model via string', function () {
        $fv = FluentVision::make();
        $fv->model('custom-model.pt');

        expect($fv->getModel())->toBe('custom-model.pt');
    });

    it('sets device', function () {
        $fv = FluentVision::make();

        $fv->useCpu();
        expect($fv->getDevice())->toBe(Device::Cpu);

        $fv->useGpu();
        expect($fv->getDevice())->toBe(Device::Gpu);
    });

    it('chains methods fluently', function () {
        $fv = FluentVision::make()
            ->useUltralytics()
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->confidence(0.5)
            ->iou(0.45)
            ->imgsz(1280)
            ->maxDet(100);

        expect($fv->getProvider())->toBe(Provider::Ultralytics)
            ->and($fv->getModel())->toBe('yolo26s.pt')
            ->and($fv->getDevice())->toBe(Device::Cpu);
    });

    it('sets YOLOE model via enum', function () {
        $fv = FluentVision::make();
        $fv->model(YoloModel::YOLOE26s);

        expect($fv->getModel())->toBe('yoloe-26s-seg.pt');
    });

    it('sets YOLOE prompt-free model via enum', function () {
        $fv = FluentVision::make();
        $fv->model(YoloModel::YOLOE26sPF);

        expect($fv->getModel())->toBe('yoloe-26s-seg-pf.pt');
    });

    it('throws when detecting without media', function () {
        $fv = FluentVision::make();

        expect(fn () => $fv->detect())->toThrow(RuntimeException::class);
    });

    it('throws when annotating without media', function () {
        $fv = FluentVision::make();

        expect(fn () => $fv->annotate())->toThrow(RuntimeException::class);
    });

    it('loads config from custom path', function () {
        $fv = FluentVision::make(__DIR__.'/../fixtures/test-config.php');

        expect($fv->getConfig()->modelDir())->toBe('/tmp/fluentvision-test-models');
    });

    it('sets model via absolute path string', function () {
        $fv = FluentVision::make();
        $fv->model('/home/user/models/my-custom-model.pt');

        expect($fv->getModel())->toBe('/home/user/models/my-custom-model.pt');
    });

    it('sets nanodetCustom with config and checkpoint', function () {
        $fv = FluentVision::make();
        $fv->nanodetCustom('/path/to/config.yml', '/path/to/checkpoint.ckpt');

        expect($fv)->toBeInstanceOf(FluentVision::class);
    });

    it('auto-infers ultralytics provider from .pt model path', function () {
        $fv = FluentVision::make();
        $fv->model('my-trained-model.pt');

        expect($fv->getProvider())->toBe(Provider::Ultralytics);
    });

    it('auto-infers nanodet provider from .ckpt model path', function () {
        $fv = FluentVision::make();
        $fv->model('custom-nanodet.ckpt');

        expect($fv->getProvider())->toBe(Provider::Nanodet);
    });

    it('does not override explicitly set provider', function () {
        $fv = FluentVision::make();
        $fv->useUltralytics();
        $fv->model('custom-nanodet.ckpt');

        expect($fv->getProvider())->toBe(Provider::Ultralytics);
    });

    it('auto-infers provider from .onnx model path', function () {
        $fv = FluentVision::make();
        $fv->model('exported-model.onnx');

        expect($fv->getProvider())->toBe(Provider::Ultralytics);
    });

    it('sets savePath fluently', function () {
        $fv = FluentVision::make();
        $result = $fv->savePath('/tmp/my-output');

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('auto-infers image media type from path', function () {
        $fv = FluentVision::make();
        $fv->media('/tmp/photo.jpg');

        expect($fv->getMediaType())->toBe(MediaType::Image);
    });

    it('auto-infers video media type from path', function () {
        $fv = FluentVision::make();
        $fv->media('/tmp/clip.mp4');

        expect($fv->getMediaType())->toBe(MediaType::Video);
    });

    it('allows explicit media type override', function () {
        $fv = FluentVision::make();
        $fv->media('/tmp/somefile.dat', MediaType::Image);

        expect($fv->getMediaType())->toBe(MediaType::Image);
    });

    it('media method chains fluently', function () {
        $fv = FluentVision::make();
        $result = $fv->media('/tmp/photo.jpg');

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('sets stream source and callback fluently', function () {
        $fv = FluentVision::make();
        $result = $fv->media('rtsp://example.com/live')
            ->streamConfig(static fn () => null);

        expect($result)->toBeInstanceOf(FluentVision::class)
            ->and($fv->getMediaType())->toBe(MediaType::Stream);
    });

    it('passes maxFramesToProcess option via streamConfig', function () {
        $expectedResult = new StreamResult(
            source: 'rtsp://test',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
        );
        $expectedResult->setTotalTime(1.0);

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $streamService = Mockery::mock(StreamServiceInterface::class);
        $streamService->shouldReceive('stream')
            ->once()
            ->withArgs(function (Provider $p, string $src, string $m, Device $d, callable $cb, array $opts) {
                return isset($opts['max_frames']) && $opts['max_frames'] === 100;
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            streamService: $streamService,
        );

        $result = $fv->media('rtsp://test')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->streamConfig(static fn () => null, null, 100)
            ->process();

        expect($result)->toBe($expectedResult);

        Mockery::close();
    });

    it('throws when process called on stream without callback', function () {
        $fv = FluentVision::make();
        $fv->media('rtsp://test');

        expect(fn () => $fv->process())->toThrow(RuntimeException::class);
    });

    it('delegates stream to StreamServiceInterface via process', function () {
        $expectedResult = new StreamResult(
            source: 'rtsp://test',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
        );
        $expectedResult->setStopped(true);
        $expectedResult->setTotalTime(1.0);

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $streamService = Mockery::mock(StreamServiceInterface::class);
        $streamService->shouldReceive('stream')
            ->once()
            ->withArgs(function (Provider $p, string $src, string $m, Device $d, callable $cb, array $opts) {
                return $src === 'rtsp://test' && $m === '/home/user/.fluentvision/models/yolo26s.pt';
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            streamService: $streamService,
        );

        $result = $fv->media('rtsp://test')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->streamConfig(static fn () => null)
            ->process();

        expect($result)->toBe($expectedResult)
            ->and($result->source)->toBe('rtsp://test')
            ->and($result->isStopped())->toBeTrue();

        Mockery::close();
    });

    it('sets annotateStream fluently and enables annotation', function () {
        $fv = FluentVision::make();
        $result = $fv->annotateStream(8765);

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('passes annotate option in process when withAnnotation enabled', function () {
        $expectedResult = new StreamResult(
            source: 'rtsp://test',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
        );
        $expectedResult->setTotalTime(1.0);

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $streamService = Mockery::mock(StreamServiceInterface::class);
        $streamService->shouldReceive('stream')
            ->once()
            ->withArgs(function (Provider $p, string $src, string $m, Device $d, callable $cb, array $opts) {
                return isset($opts['annotate']) && $opts['annotate'] === true
                    && isset($opts['annotate_port']) && $opts['annotate_port'] === 8765;
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            streamService: $streamService,
        );

        $result = $fv->media('rtsp://test')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->withAnnotation(true)
            ->annotateStream(8765)
            ->streamConfig(static fn () => null)
            ->process();

        expect($result)->toBe($expectedResult);

        Mockery::close();
    });

    it('passes annotate option without port in process', function () {
        $expectedResult = new StreamResult(
            source: 'rtsp://test',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
        );
        $expectedResult->setTotalTime(1.0);

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $streamService = Mockery::mock(StreamServiceInterface::class);
        $streamService->shouldReceive('stream')
            ->once()
            ->withArgs(function (Provider $p, string $src, string $m, Device $d, callable $cb, array $opts) {
                return isset($opts['annotate']) && $opts['annotate'] === true
                    && ! isset($opts['annotate_port']);
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            streamService: $streamService,
        );

        $result = $fv->media('rtsp://test')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->withAnnotation(true)
            ->streamConfig(static fn () => null)
            ->process();

        expect($result)->toBe($expectedResult);

        Mockery::close();
    });

    it('streamConfig with port enables annotation', function () {
        $expectedResult = new StreamResult(
            source: 'rtsp://test',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
        );
        $expectedResult->setTotalTime(1.0);

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $streamService = Mockery::mock(StreamServiceInterface::class);
        $streamService->shouldReceive('stream')
            ->once()
            ->withArgs(function (Provider $p, string $src, string $m, Device $d, callable $cb, array $opts) {
                return isset($opts['annotate']) && $opts['annotate'] === true
                    && isset($opts['annotate_port']) && $opts['annotate_port'] === 9000;
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            streamService: $streamService,
        );

        $result = $fv->media('rtsp://test')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->streamConfig(static fn () => null, 9000)
            ->process();

        expect($result)->toBe($expectedResult);

        Mockery::close();
    });

    it('sets solution fluently', function () {
        $fv = FluentVision::make();
        $result = $fv->solution(UltralyticsSolution::Count);

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('sets solution with extra params fluently', function () {
        $fv = FluentVision::make();
        $result = $fv->solution(UltralyticsSolution::Heatmap, ['region' => '0,0,100,100,200,200,0,200']);

        expect($result)->toBeInstanceOf(FluentVision::class);
    });

    it('delegates solution to SolutionServiceInterface via process', function () {
        $expectedResult = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            frameCount: 100,
            totalTime: 10.0,
            inCount: 15,
            outCount: 12,
        );

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $solutionService = Mockery::mock(SolutionServiceInterface::class);
        $solutionService->shouldReceive('run')
            ->once()
            ->withArgs(function (UltralyticsSolution $s, string $src, string $m, Device $d, array $opts) {
                return $s === UltralyticsSolution::Count
                    && $src === '/tmp/video.mp4'
                    && $m === '/home/user/.fluentvision/models/yolo26s.pt';
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            solutionService: $solutionService,
        );

        $result = $fv->media('/tmp/video.mp4')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->solution(UltralyticsSolution::Count)
            ->process();

        expect($result)->toBe($expectedResult)
            ->and($result->inCount)->toBe(15);

        Mockery::close();
    });

    it('passes solution params and common options to SolutionService', function () {
        $expectedResult = new SolutionResult(
            solution: 'heatmap',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
        );

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $solutionService = Mockery::mock(SolutionServiceInterface::class);
        $solutionService->shouldReceive('run')
            ->once()
            ->withArgs(function (UltralyticsSolution $s, string $src, string $m, Device $d, array $opts) {
                return $s === UltralyticsSolution::Heatmap
                    && isset($opts['region']) && $opts['region'] === '0,0,100,100'
                    && isset($opts['conf']) && $opts['conf'] === 0.5;
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            solutionService: $solutionService,
        );

        $result = $fv->media('/tmp/video.mp4')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->confidence(0.5)
            ->solution(UltralyticsSolution::Heatmap, ['region' => '0,0,100,100'])
            ->process();

        expect($result)->toBe($expectedResult);

        Mockery::close();
    });

    it('throws when solution called with Nanodet provider', function () {
        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $solutionService = Mockery::mock(SolutionServiceInterface::class);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            solutionService: $solutionService,
        );

        $fv->media('/tmp/video.mp4')
            ->useNanodet()
            ->model(NanodetModel::PlusM416)
            ->solution(UltralyticsSolution::Count);

        expect(fn () => $fv->process())->toThrow(RuntimeException::class, 'Solutions are only available with the Ultralytics provider.');

        Mockery::close();
    });

    it('passes save option when withAnnotation enabled on solution', function () {
        $expectedResult = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            annotatedPath: '/tmp/output/annotated.mp4',
        );

        $inferenceService = Mockery::mock(InferenceServiceInterface::class);
        $modelService = Mockery::mock(ModelServiceInterface::class);
        $modelService->shouldReceive('resolveUltralyticsModel')->andReturn('/home/user/.fluentvision/models/yolo26s.pt');

        $solutionService = Mockery::mock(SolutionServiceInterface::class);
        $solutionService->shouldReceive('run')
            ->once()
            ->withArgs(function (UltralyticsSolution $s, string $src, string $m, Device $d, array $opts) {
                return isset($opts['save']) && $opts['save'] === true
                    && isset($opts['save_path']);
            })
            ->andReturn($expectedResult);

        $fv = new FluentVision(
            inferenceService: $inferenceService,
            modelService: $modelService,
            solutionService: $solutionService,
        );

        $result = $fv->media('/tmp/video.mp4')
            ->model(YoloModel::YOLO26s)
            ->useCpu()
            ->withAnnotation(true)
            ->solution(UltralyticsSolution::Count)
            ->process();

        expect($result->hasAnnotation())->toBeTrue();

        Mockery::close();
    });
});
