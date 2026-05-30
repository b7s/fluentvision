<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
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

    it('throws when detecting without image', function () {
        $fv = FluentVision::make();

        expect(fn () => $fv->detect())->toThrow(RuntimeException::class);
    });

    it('throws when detecting video without video path', function () {
        $fv = FluentVision::make();

        expect(fn () => $fv->detectVideo())->toThrow(RuntimeException::class);
    });

    it('throws when annotating without image', function () {
        $fv = FluentVision::make();

        expect(fn () => $fv->annotate())->toThrow(RuntimeException::class);
    });

    it('loads config from custom path', function () {
        $fv = FluentVision::make(__DIR__.'/../fixtures/test-config.php');

        expect($fv->getConfig()->modelDir())->toBe('/tmp/fluentvision-test-models');
    });
});
