<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\YoloTask;

describe('Provider enum', function () {
    it('has correct cases', function () {
        expect(Provider::cases())->toHaveCount(2)
            ->and(Provider::Ultralytics->value)->toBe('ultralytics')
            ->and(Provider::Nanodet->value)->toBe('nanodet');
    });

    it('returns correct labels', function () {
        expect(Provider::Ultralytics->label())->toBe('Ultralytics YOLO26')
            ->and(Provider::Nanodet->label())->toBe('NanoDet-Plus');
    });

    it('checks provider type', function () {
        expect(Provider::Ultralytics->isUltralytics())->toBeTrue()
            ->and(Provider::Ultralytics->isNanodet())->toBeFalse()
            ->and(Provider::Nanodet->isNanodet())->toBeTrue()
            ->and(Provider::Nanodet->isUltralytics())->toBeFalse();
    });

    it('returns values array', function () {
        expect(Provider::values())->toBe(['ultralytics', 'nanodet']);
    });

    it('returns options array', function () {
        $options = Provider::options();
        expect($options)->toHaveKey('ultralytics')
            ->and($options)->toHaveKey('nanodet');
    });

    it('infers provider from .pt extension', function () {
        expect(Provider::inferFromModel('my-custom-model.pt'))->toBe(Provider::Ultralytics);
    });

    it('infers provider from .onnx extension', function () {
        expect(Provider::inferFromModel('model.onnx'))->toBe(Provider::Ultralytics);
    });

    it('infers provider from .engine extension', function () {
        expect(Provider::inferFromModel('model.engine'))->toBe(Provider::Ultralytics);
    });

    it('infers provider from .ckpt extension', function () {
        expect(Provider::inferFromModel('custom-nanodet.ckpt'))->toBe(Provider::Nanodet);
    });

    it('returns null for unknown extension', function () {
        expect(Provider::inferFromModel('model.unknown'))->toBeNull()
            ->and(Provider::inferFromModel('model'))->toBeNull();
    });

    it('infers provider from absolute path', function () {
        expect(Provider::inferFromModel('/home/user/models/custom.pt'))->toBe(Provider::Ultralytics)
            ->and(Provider::inferFromModel('/home/user/models/custom.ckpt'))->toBe(Provider::Nanodet);
    });

    it('lists ultralytics and nanodet extensions', function () {
        expect(Provider::ultralyticsExtensions())->toContain('pt', 'onnx', 'engine')
            ->and(Provider::nanodetExtensions())->toBe(['ckpt']);
    });
});

describe('YoloModel enum', function () {
    it('has correct cases', function () {
        expect(YoloModel::cases())->toHaveCount(11);
    });

    it('returns correct filenames', function () {
        expect(YoloModel::YOLO26s->filename())->toBe('yolo26s.pt')
            ->and(YoloModel::YOLO26n->filename())->toBe('yolo26n.pt')
            ->and(YoloModel::YOLOE26s->filename())->toBe('yoloe-26s-seg.pt')
            ->and(YoloModel::YOLOE26sPF->filename())->toBe('yoloe-26s-seg-pf.pt');
    });

    it('returns download URLs', function () {
        expect(YoloModel::YOLO26s->downloadUrl())->toContain('yolo26s.pt')
            ->and(YoloModel::YOLO26s->downloadUrl())->toContain('v8.4.0')
            ->and(YoloModel::YOLOE26s->downloadUrl())->toContain('yoloe-26s-seg.pt');
    });

    it('returns values and options', function () {
        expect(YoloModel::values())->toHaveCount(11)
            ->and(YoloModel::options())->toHaveKey('yolo26s.pt');
    });

    it('identifies YOLOE models', function () {
        expect(YoloModel::YOLOE26s->isYoloe())->toBeTrue()
            ->and(YoloModel::YOLOE26m->isYoloe())->toBeTrue()
            ->and(YoloModel::YOLOE26l->isYoloe())->toBeTrue()
            ->and(YoloModel::YOLOE26sPF->isYoloe())->toBeTrue()
            ->and(YoloModel::YOLOE26mPF->isYoloe())->toBeTrue()
            ->and(YoloModel::YOLOE26lPF->isYoloe())->toBeTrue()
            ->and(YoloModel::YOLO26s->isYoloe())->toBeFalse();
    });

    it('identifies prompt-free models', function () {
        expect(YoloModel::YOLOE26sPF->isPromptFree())->toBeTrue()
            ->and(YoloModel::YOLOE26mPF->isPromptFree())->toBeTrue()
            ->and(YoloModel::YOLOE26lPF->isPromptFree())->toBeTrue()
            ->and(YoloModel::YOLOE26s->isPromptFree())->toBeFalse()
            ->and(YoloModel::YOLO26s->isPromptFree())->toBeFalse();
    });

    it('identifies models that support prompts', function () {
        expect(YoloModel::YOLOE26s->supportsPrompts())->toBeTrue()
            ->and(YoloModel::YOLOE26m->supportsPrompts())->toBeTrue()
            ->and(YoloModel::YOLOE26l->supportsPrompts())->toBeTrue()
            ->and(YoloModel::YOLOE26sPF->supportsPrompts())->toBeFalse()
            ->and(YoloModel::YOLO26s->supportsPrompts())->toBeFalse();
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
        expect(NanodetModel::PlusM416->configFilename())->toBe('config/nanodet-plus-m_416.yml')
            ->and(NanodetModel::PlusM416->checkpointFilename())->toBe('nanodet-plus-m_416_checkpoint.ckpt');
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
        expect(YoloTask::Detect->modelSuffix())->toBe('')
            ->and(YoloTask::Segment->modelSuffix())->toBe('-seg')
            ->and(YoloTask::Classify->modelSuffix())->toBe('-cls')
            ->and(YoloTask::Pose->modelSuffix())->toBe('-pose')
            ->and(YoloTask::Obb->modelSuffix())->toBe('-obb');
    });
});

describe('Device enum', function () {
    it('has correct cases', function () {
        expect(Device::cases())->toHaveCount(2);
    });

    it('maps to correct Ultralytics args', function () {
        expect(Device::Cpu->toUltralyticsArg())->toBe('cpu')
            ->and(Device::Gpu->toUltralyticsArg())->toBe('0');
    });

    it('maps to correct NanoDet args', function () {
        expect(Device::Cpu->toNanodetArg())->toBe('cpu')
            ->and(Device::Gpu->toNanodetArg())->toBe('cuda:0');
    });

    it('checks device type', function () {
        expect(Device::Cpu->isCpu())->toBeTrue()
            ->and(Device::Cpu->isGpu())->toBeFalse()
            ->and(Device::Gpu->isGpu())->toBeTrue();
    });
});
