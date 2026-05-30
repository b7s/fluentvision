<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\YoloTask;

describe('Provider enum', function () {
    it('has correct cases', function () {
        expect(Provider::cases())->toHaveCount(2);
        expect(Provider::Ultralytics->value)->toBe('ultralytics');
        expect(Provider::Nanodet->value)->toBe('nanodet');
    });

    it('returns correct labels', function () {
        expect(Provider::Ultralytics->label())->toBe('Ultralytics YOLO26');
        expect(Provider::Nanodet->label())->toBe('NanoDet-Plus');
    });

    it('checks provider type', function () {
        expect(Provider::Ultralytics->isUltralytics())->toBeTrue();
        expect(Provider::Ultralytics->isNanodet())->toBeFalse();
        expect(Provider::Nanodet->isNanodet())->toBeTrue();
        expect(Provider::Nanodet->isUltralytics())->toBeFalse();
    });

    it('returns values array', function () {
        expect(Provider::values())->toBe(['ultralytics', 'nanodet']);
    });

    it('returns options array', function () {
        $options = Provider::options();
        expect($options)->toHaveKey('ultralytics');
        expect($options)->toHaveKey('nanodet');
    });
});

describe('YoloModel enum', function () {
    it('has correct cases', function () {
        expect(YoloModel::cases())->toHaveCount(5);
    });

    it('returns correct filenames', function () {
        expect(YoloModel::YOLO26s->filename())->toBe('yolo26s.pt');
        expect(YoloModel::YOLO26n->filename())->toBe('yolo26n.pt');
    });

    it('returns download URLs', function () {
        expect(YoloModel::YOLO26s->downloadUrl())->toContain('yolo26s.pt');
        expect(YoloModel::YOLO26s->downloadUrl())->toContain('v8.4.0');
    });

    it('returns values and options', function () {
        expect(YoloModel::values())->toHaveCount(5);
        expect(YoloModel::options())->toHaveKey('yolo26s.pt');
    });
});

describe('NanodetModel enum', function () {
    it('has correct cases', function () {
        expect(NanodetModel::cases())->toHaveCount(7);
    });

    it('returns correct dirname', function () {
        expect(NanodetModel::PlusM416->dirname())->toBe('nanodet-plus-m-416');
    });

    it('returns config and checkpoint filenames', function () {
        expect(NanodetModel::PlusM416->configFilename())->toBe('config/nanodet-plus-m_416.yml');
        expect(NanodetModel::PlusM416->checkpointFilename())->toBe('nanodet-plus-m_416_checkpoint.ckpt');
    });

    it('returns checkpoint URL', function () {
        expect(NanodetModel::PlusM416->checkpointUrl())->toContain('v1.0.0-alpha-1');
    });
});

describe('YoloTask enum', function () {
    it('has correct cases', function () {
        expect(YoloTask::cases())->toHaveCount(5);
    });

    it('returns model suffixes', function () {
        expect(YoloTask::Detect->modelSuffix())->toBe('');
        expect(YoloTask::Segment->modelSuffix())->toBe('-seg');
        expect(YoloTask::Classify->modelSuffix())->toBe('-cls');
        expect(YoloTask::Pose->modelSuffix())->toBe('-pose');
        expect(YoloTask::Obb->modelSuffix())->toBe('-obb');
    });
});

describe('Device enum', function () {
    it('has correct cases', function () {
        expect(Device::cases())->toHaveCount(2);
    });

    it('maps to correct Ultralytics args', function () {
        expect(Device::Cpu->toUltralyticsArg())->toBe('cpu');
        expect(Device::Gpu->toUltralyticsArg())->toBe('0');
    });

    it('maps to correct NanoDet args', function () {
        expect(Device::Cpu->toNanodetArg())->toBe('cpu');
        expect(Device::Gpu->toNanodetArg())->toBe('cuda:0');
    });

    it('checks device type', function () {
        expect(Device::Cpu->isCpu())->toBeTrue();
        expect(Device::Cpu->isGpu())->toBeFalse();
        expect(Device::Gpu->isGpu())->toBeTrue();
    });
});
