<?php

declare(strict_types=1);

use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\StreamResult;
use B7s\FluentVision\Support\BoundingBox;

describe('StreamResult', function () {
    it('creates with required properties', function () {
        $result = new StreamResult(
            source: 'rtsp://example.com/stream',
            provider: 'ultralytics',
            model: 'yolo26s.pt',
        );

        expect($result->source)->toBe('rtsp://example.com/stream')
            ->and($result->provider)->toBe('ultralytics')
            ->and($result->model)->toBe('yolo26s.pt')
            ->and($result->getTotalTime())->toBe(0.0)
            ->and($result->isStopped())->toBeFalse()
            ->and($result->isRunning())->toBeFalse()
            ->and($result->getStreamUrl())->toBeNull();
    });

    it('accumulates frames via addFrame', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        $frame1 = new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.05);
        $frame2 = new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.06);

        $result->addFrame($frame1);
        $result->addFrame($frame2);

        expect($result->getFrameCount())->toBe(2)
            ->and($result->getFrames())->toHaveCount(2);
    });

    it('returns zero frame count for empty frames', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        expect($result->getFrameCount())->toBe(0);
    });

    it('returns total detections across all frames', function () {
        $detections1 = [
            new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
            new DetectionResult(class: 'car', confidence: 0.8, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
        ];
        $detections2 = [
            new DetectionResult(class: 'person', confidence: 0.85, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200)),
        ];

        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $result->addFrame(new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections1, inferenceTime: 0.05));
        $result->addFrame(new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections2, inferenceTime: 0.06));

        expect($result->getTotalDetections())->toBe(3);
    });

    it('returns zero total detections for empty frames', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        expect($result->getTotalDetections())->toBe(0);
    });

    it('returns average inference time', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $result->addFrame(new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.10));
        $result->addFrame(new InferenceResult(imagePath: '', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.20));

        expect($result->getAverageInferenceTime())->toEqualWithDelta(0.15, 0.0001);
    });

    it('returns zero average inference time for empty frames', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        expect($result->getAverageInferenceTime())->toBe(0.0);
    });

    it('setTotalTime and getTotalTime work', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $result->setTotalTime(1.234);

        expect($result->getTotalTime())->toBe(1.234);
    });

    it('setStopped and isStopped work', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        expect($result->isStopped())->toBeFalse();

        $result->setStopped(true);

        expect($result->isStopped())->toBeTrue();
    });

    it('setStreamUrl and getStreamUrl work', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        expect($result->getStreamUrl())->toBeNull();

        $result->setStreamUrl('http://localhost:8765/stream');

        expect($result->getStreamUrl())->toBe('http://localhost:8765/stream');
    });

    it('setRunning and isRunning work', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        expect($result->isRunning())->toBeFalse();

        $result->setRunning(true);

        expect($result->isRunning())->toBeTrue();
    });

    it('converts to array', function () {
        $detections = [new DetectionResult(class: 'person', confidence: 0.9, box: new BoundingBox(x1: 0, y1: 0, x2: 100, y2: 200))];

        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $result->addFrame(new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: $detections, inferenceTime: 0.05));
        $result->setTotalTime(1.5);
        $result->setStopped(true);

        $array = $result->toArray();

        expect($array['source'])->toBe('rtsp://test')
            ->and($array['provider'])->toBe('ultralytics')
            ->and($array['model'])->toBe('yolo26s.pt')
            ->and($array['frame_count'])->toBe(1)
            ->and($array['total_detections'])->toBe(1)
            ->and($array['total_time'])->toBe(1.5)
            ->and($array['average_inference_time'])->toBe(0.05)
            ->and($array['stopped'])->toBeTrue()
            ->and($array['frames'])->toHaveCount(1);
    });

    it('converts to json', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $json = $result->toJson();

        expect($json)->toBeJson()
            ->and(json_decode($json, true)['source'])->toBe('rtsp://test');
    });

    it('creates from array', function () {
        $frames = [
            new InferenceResult(imagePath: 'rtsp://stream', provider: 'ultralytics', model: 'yolo26s.pt', detections: [], inferenceTime: 0.05),
        ];

        $result = StreamResult::fromArray([
            'source' => 'rtsp://test',
            'provider' => 'ultralytics',
            'model' => 'yolo26s.pt',
            'total_time' => 1.5,
            'stopped' => true,
        ], $frames);

        expect($result->source)->toBe('rtsp://test')
            ->and($result->provider)->toBe('ultralytics')
            ->and($result->model)->toBe('yolo26s.pt')
            ->and($result->getTotalTime())->toBe(1.5)
            ->and($result->isStopped())->toBeTrue()
            ->and($result->getFrameCount())->toBe(1);
    });

    it('creates from array with defaults for missing keys', function () {
        $result = StreamResult::fromArray([], []);

        expect($result->source)->toBe('')
            ->and($result->provider)->toBe('')
            ->and($result->model)->toBe('')
            ->and($result->getTotalTime())->toBe(0.0)
            ->and($result->isStopped())->toBeFalse()
            ->and($result->getFrameCount())->toBe(0);
    });

    it('includes streamUrl in toArray when present', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $result->setStreamUrl('http://localhost:8765/stream');

        $array = $result->toArray();

        expect($array)->toHaveKey('stream_url')
            ->and($array['stream_url'])->toBe('http://localhost:8765/stream');
    });

    it('omits streamUrl from toArray when null', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        $array = $result->toArray();

        expect($array)->not->toHaveKey('stream_url');
    });

    it('creates from array with stream_url', function () {
        $result = StreamResult::fromArray([
            'source' => 'rtsp://test',
            'provider' => 'ultralytics',
            'model' => 'yolo26s.pt',
            'total_time' => 1.5,
            'stopped' => true,
            'stream_url' => 'http://localhost:8765/stream',
        ], []);

        expect($result->getStreamUrl())->toBe('http://localhost:8765/stream');
    });

    it('stopStream requests stop and calls kill callback', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $killed = false;
        $result->setKillCallback(static function () use (&$killed): void {
            $killed = true;
        });

        expect($result->isStopRequested())->toBeFalse();

        $result->stopStream();

        expect($result->isStopRequested())->toBeTrue()
            ->and($killed)->toBeTrue();
    });

    it('stopStream works without kill callback', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');

        $result->stopStream();

        expect($result->isStopRequested())->toBeTrue();
    });

    it('toArray includes running field', function () {
        $result = new StreamResult(source: 'rtsp://test', provider: 'ultralytics', model: 'yolo26s.pt');
        $result->setRunning(true);

        $array = $result->toArray();

        expect($array['running'])->toBeTrue();
    });
});
