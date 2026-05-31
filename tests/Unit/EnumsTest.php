<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
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

describe('MediaType enum', function () {
    it('has correct cases', function () {
        expect(MediaType::cases())->toHaveCount(3)
            ->and(MediaType::Image->value)->toBe('image')
            ->and(MediaType::Video->value)->toBe('video')
            ->and(MediaType::Stream->value)->toBe('stream');
    });

    it('returns correct labels', function () {
        expect(MediaType::Image->label())->toBe('Image')
            ->and(MediaType::Video->label())->toBe('Video')
            ->and(MediaType::Stream->label())->toBe('Stream');
    });

    it('checks media type', function () {
        expect(MediaType::Image->isImage())->toBeTrue()
            ->and(MediaType::Image->isVideo())->toBeFalse()
            ->and(MediaType::Image->isStream())->toBeFalse()
            ->and(MediaType::Video->isVideo())->toBeTrue()
            ->and(MediaType::Video->isImage())->toBeFalse()
            ->and(MediaType::Video->isStream())->toBeFalse()
            ->and(MediaType::Stream->isStream())->toBeTrue()
            ->and(MediaType::Stream->isImage())->toBeFalse()
            ->and(MediaType::Stream->isVideo())->toBeFalse();
    });

    it('infers image type from path', function () {
        expect(MediaType::inferFromPath('/tmp/photo.jpg'))->toBe(MediaType::Image)
            ->and(MediaType::inferFromPath('/tmp/photo.png'))->toBe(MediaType::Image)
            ->and(MediaType::inferFromPath('/tmp/photo.webp'))->toBe(MediaType::Image);
    });

    it('infers video type from path', function () {
        expect(MediaType::inferFromPath('/tmp/clip.mp4'))->toBe(MediaType::Video)
            ->and(MediaType::inferFromPath('/tmp/clip.avi'))->toBe(MediaType::Video)
            ->and(MediaType::inferFromPath('/tmp/clip.mov'))->toBe(MediaType::Video);
    });

    it('infers stream type from rtsp source', function () {
        expect(MediaType::inferFromPath('rtsp://192.168.1.100:554/stream'))->toBe(MediaType::Stream)
            ->and(MediaType::inferFromPath('rtmp://server/live/stream'))->toBe(MediaType::Stream)
            ->and(MediaType::inferFromPath('tcp://192.168.1.1:5000'))->toBe(MediaType::Stream)
            ->and(MediaType::inferFromPath('udp://239.0.0.1:1234'))->toBe(MediaType::Stream)
            ->and(MediaType::inferFromPath('0'))->toBe(MediaType::Stream)
            ->and(MediaType::inferFromPath('1'))->toBe(MediaType::Stream);
    });

    it('detects stream sources', function () {
        expect(MediaType::isStreamSource('rtsp://cam.local/stream'))->toBeTrue()
            ->and(MediaType::isStreamSource('rtmp://server/live'))->toBeTrue()
            ->and(MediaType::isStreamSource('tcp://host:5000'))->toBeTrue()
            ->and(MediaType::isStreamSource('udp://host:5000'))->toBeTrue()
            ->and(MediaType::isStreamSource('http://cam.local/mjpeg'))->toBeTrue()
            ->and(MediaType::isStreamSource('https://cam.local/mjpeg'))->toBeTrue()
            ->and(MediaType::isStreamSource('0'))->toBeTrue()
            ->and(MediaType::isStreamSource('/tmp/video.mp4'))->toBeFalse()
            ->and(MediaType::isStreamSource('image.jpg'))->toBeFalse();
    });

    it('defaults to image for unknown extensions', function () {
        expect(MediaType::inferFromPath('/tmp/file.dat'))->toBe(MediaType::Image)
            ->and(MediaType::inferFromPath('/tmp/file.unknown'))->toBe(MediaType::Image);
    });

    it('lists video extensions', function () {
        expect(MediaType::videoExtensions())->toContain('mp4', 'avi', 'mov', 'mkv', 'webm');
    });

    it('lists image extensions', function () {
        expect(MediaType::imageExtensions())->toContain('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
    });
});
