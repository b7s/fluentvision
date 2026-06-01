<?php

declare(strict_types=1);

use B7s\FluentVision\Results\SolutionResult;

describe('SolutionResult', function () {
    it('creates with required properties', function () {
        $result = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
        );

        expect($result->solution)->toBe('count')
            ->and($result->source)->toBe('/tmp/video.mp4')
            ->and($result->model)->toBe('yolo26s.pt')
            ->and($result->provider)->toBe('ultralytics')
            ->and($result->frameCount)->toBe(0)
            ->and($result->totalTime)->toBe(0.0);
    });

    it('creates with all solution-specific properties', function () {
        $result = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            frameCount: 150,
            totalTime: 12.5,
            totalTracks: 42,
            inCount: 10,
            outCount: 8,
            classwiseCount: ['person' => 10, 'car' => 8],
            queueCount: 5,
            totalCropObjects: 20,
            pixelsDistance: 150.5,
            workoutCount: [1 => 10],
            workoutAngle: [1 => 85.0],
            workoutStage: [1 => 'up'],
            filledSlots: 3,
            availableSlots: 7,
            speedDict: ['1' => 45.5, '2' => 30.0],
            emailSent: true,
            regionCounts: ['A' => 5, 'B' => 3],
            annotatedPath: '/tmp/output/annotated.mp4',
            frames: [['frame' => 0, 'count' => 2]],
        );

        expect($result->inCount)->toBe(10)
            ->and($result->outCount)->toBe(8)
            ->and($result->totalTracks)->toBe(42)
            ->and($result->queueCount)->toBe(5)
            ->and($result->pixelsDistance)->toBe(150.5)
            ->and($result->filledSlots)->toBe(3)
            ->and($result->availableSlots)->toBe(7)
            ->and($result->emailSent)->toBeTrue()
            ->and($result->annotatedPath)->toBe('/tmp/output/annotated.mp4');
    });

    it('converts to array with only non-default fields', function () {
        $result = new SolutionResult(
            solution: 'heatmap',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            frameCount: 100,
            totalTime: 8.0,
            inCount: 5,
        );

        $array = $result->toArray();

        expect($array['solution'])->toBe('heatmap')
            ->and($array['frame_count'])->toBe(100)
            ->and($array['total_time'])->toBe(8.0)
            ->and($array['in_count'])->toBe(5)
            ->and($array)->not->toHaveKey('out_count')
            ->and($array)->not->toHaveKey('total_tracks')
            ->and($array)->not->toHaveKey('speed_dict');
    });

    it('includes nullable fields in array when set', function () {
        $result = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            classwiseCount: ['person' => 5],
            speedDict: ['1' => 30.0],
            regionCounts: ['A' => 3],
        );

        $array = $result->toArray();

        expect($array['classwise_count'])->toBe(['person' => 5])
            ->and($array['speed_dict'])->toBe(['1' => 30.0])
            ->and($array['region_counts'])->toBe(['A' => 3]);
    });

    it('converts to JSON', function () {
        $result = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            inCount: 5,
        );

        $json = $result->toJson();
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        expect($decoded['solution'])->toBe('count')
            ->and($decoded['in_count'])->toBe(5);
    });

    it('creates from array with all fields', function () {
        $data = [
            'solution' => 'count',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
            'frame_count' => 200,
            'total_time' => 15.0,
            'total_tracks' => 30,
            'in_count' => 12,
            'out_count' => 10,
            'classwise_count' => ['person' => 12, 'car' => 10],
            'queue_count' => 4,
            'pixels_distance' => 200.5,
            'speed_dict' => ['1' => 55.0],
            'region_counts' => ['A' => 8],
            'annotated_path' => '/tmp/out.mp4',
        ];

        $result = SolutionResult::fromArray($data);

        expect($result->solution)->toBe('count')
            ->and($result->frameCount)->toBe(200)
            ->and($result->totalTime)->toBe(15.0)
            ->and($result->totalTracks)->toBe(30)
            ->and($result->inCount)->toBe(12)
            ->and($result->outCount)->toBe(10)
            ->and($result->queueCount)->toBe(4)
            ->and($result->pixelsDistance)->toBe(200.5)
            ->and($result->annotatedPath)->toBe('/tmp/out.mp4');
    });

    it('creates from array with fallback inference_time key', function () {
        $data = [
            'solution' => 'count',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
            'inference_time' => 3.5,
        ];

        $result = SolutionResult::fromArray($data);

        expect($result->totalTime)->toBe(3.5);
    });

    it('creates from array with missing keys using defaults', function () {
        $result = SolutionResult::fromArray([]);

        expect($result->solution)->toBe('')
            ->and($result->source)->toBe('')
            ->and($result->model)->toBe('')
            ->and($result->provider)->toBe('')
            ->and($result->frameCount)->toBe(0)
            ->and($result->totalTime)->toBe(0.0)
            ->and($result->totalTracks)->toBeNull()
            ->and($result->inCount)->toBeNull()
            ->and($result->classwiseCount)->toBe([])
            ->and($result->speedDict)->toBe([])
            ->and($result->regionCounts)->toBe([]);
    });

    it('round-trips fromArray → toArray', function () {
        $data = [
            'solution' => 'speed',
            'source' => '/tmp/road.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
            'frame_count' => 300,
            'total_time' => 20.0,
            'speed_dict' => ['1' => 60.0, '2' => 45.0],
            'total_tracks' => 2,
        ];

        $result = SolutionResult::fromArray($data);
        $array = $result->toArray();

        expect($array['solution'])->toBe('speed')
            ->and($array['frame_count'])->toBe(300)
            ->and($array['speed_dict'])->toBe(['1' => 60.0, '2' => 45.0])
            ->and($array['total_tracks'])->toBe(2);
    });

    it('getter methods work', function () {
        $result = new SolutionResult(
            solution: 'queue',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            frameCount: 50,
            totalTime: 5.0,
            totalTracks: 10,
            queueCount: 3,
        );

        expect($result->getFrameCount())->toBe(50)
            ->and($result->getTotalTime())->toBe(5.0)
            ->and($result->getTotalTracks())->toBe(10)
            ->and($result->getQueueCount())->toBe(3);
    });

    it('hasAnnotation returns true only when annotatedPath is set', function () {
        $without = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
        );
        $with = new SolutionResult(
            solution: 'count',
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            provider: 'ultralytics',
            annotatedPath: '/tmp/out.mp4',
        );

        expect($without->hasAnnotation())->toBeFalse()
            ->and($with->hasAnnotation())->toBeTrue()
            ->and($with->getAnnotatedPath())->toBe('/tmp/out.mp4');
    });
});
