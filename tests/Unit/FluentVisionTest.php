<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;

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
            ->conf(0.5)
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
});
