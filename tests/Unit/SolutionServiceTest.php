<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\UltralyticsSolution;
use B7s\FluentVision\Results\SolutionResult;
use B7s\FluentVision\Services\PythonService;
use B7s\FluentVision\Services\SolutionService;

describe('SolutionService', function () {
    it('builds arguments and calls PythonService', function () {
        $json = json_encode([
            'solution' => 'count',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
            'frame_count' => 100,
            'total_time' => 5.0,
            'in_count' => 10,
            'out_count' => 8,
        ], JSON_THROW_ON_ERROR);

        $pythonService = Mockery::mock(PythonService::class);
        $pythonService->shouldReceive('executeScript')
            ->once()
            ->withArgs(function (string $script, array $args) {
                return str_contains($script, 'ultralytics_solution.py')
                    && in_array('--solution', $args, true)
                    && in_array('count', $args, true)
                    && in_array('--source', $args, true)
                    && in_array('/tmp/video.mp4', $args, true)
                    && in_array('--model', $args, true)
                    && in_array('--device', $args, true)
                    && in_array('cpu', $args, true);
            })
            ->andReturn($json);

        $service = new SolutionService($pythonService);
        $result = $service->run(
            solution: UltralyticsSolution::Count,
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            device: Device::Cpu,
        );

        expect($result)->toBeInstanceOf(SolutionResult::class)
            ->and($result->solution)->toBe('count')
            ->and($result->inCount)->toBe(10)
            ->and($result->outCount)->toBe(8);

        Mockery::close();
    });

    it('passes optional args when provided', function () {
        $json = json_encode([
            'solution' => 'heatmap',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
        ], JSON_THROW_ON_ERROR);

        $pythonService = Mockery::mock(PythonService::class);
        $pythonService->shouldReceive('executeScript')
            ->once()
            ->withArgs(function (string $script, array $args) {
                return in_array('--region', $args, true)
                    && in_array('--conf', $args, true)
                    && in_array('--colormap', $args, true);
            })
            ->andReturn($json);

        $service = new SolutionService($pythonService);
        $result = $service->run(
            solution: UltralyticsSolution::Heatmap,
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            device: Device::Cpu,
            options: ['region' => '0,0,100,100', 'conf' => 0.5, 'colormap' => 2],
        );

        expect($result->solution)->toBe('heatmap');

        Mockery::close();
    });

    it('passes blur_ratio for blur solution', function () {
        $json = json_encode([
            'solution' => 'blur',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
        ], JSON_THROW_ON_ERROR);

        $pythonService = Mockery::mock(PythonService::class);
        $pythonService->shouldReceive('executeScript')
            ->once()
            ->withArgs(function (string $script, array $args) {
                return in_array('--blur-ratio', $args, true);
            })
            ->andReturn($json);

        $service = new SolutionService($pythonService);
        $service->run(
            solution: UltralyticsSolution::Blur,
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            device: Device::Cpu,
            options: ['blur_ratio' => 0.5],
        );

        Mockery::close();
    });

    it('passes speed-specific args', function () {
        $json = json_encode([
            'solution' => 'speed',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
        ], JSON_THROW_ON_ERROR);

        $pythonService = Mockery::mock(PythonService::class);
        $pythonService->shouldReceive('executeScript')
            ->once()
            ->withArgs(function (string $script, array $args) {
                return in_array('--meter-per-pixel', $args, true)
                    && in_array('--max-speed', $args, true);
            })
            ->andReturn($json);

        $service = new SolutionService($pythonService);
        $service->run(
            solution: UltralyticsSolution::Speed,
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            device: Device::Cpu,
            options: ['meter_per_pixel' => 0.05, 'max_speed' => 120],
        );

        Mockery::close();
    });

    it('passes workout-specific args', function () {
        $json = json_encode([
            'solution' => 'workout',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
        ], JSON_THROW_ON_ERROR);

        $pythonService = Mockery::mock(PythonService::class);
        $pythonService->shouldReceive('executeScript')
            ->once()
            ->withArgs(function (string $script, array $args) {
                return in_array('--kpts', $args, true)
                    && in_array('--up-angle', $args, true)
                    && in_array('--down-angle', $args, true);
            })
            ->andReturn($json);

        $service = new SolutionService($pythonService);
        $service->run(
            solution: UltralyticsSolution::Workout,
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            device: Device::Cpu,
            options: ['kpts' => '0,1,2', 'up_angle' => 145.0, 'down_angle' => 90.0],
        );

        Mockery::close();
    });

    it('passes analytics-specific args', function () {
        $json = json_encode([
            'solution' => 'analytics',
            'source' => '/tmp/video.mp4',
            'model' => 'yolo26s.pt',
            'provider' => 'ultralytics',
        ], JSON_THROW_ON_ERROR);

        $pythonService = Mockery::mock(PythonService::class);
        $pythonService->shouldReceive('executeScript')
            ->once()
            ->withArgs(function (string $script, array $args) {
                return in_array('--analytics-type', $args, true)
                    && in_array('--json-file', $args, true)
                    && in_array('--records', $args, true);
            })
            ->andReturn($json);

        $service = new SolutionService($pythonService);
        $service->run(
            solution: UltralyticsSolution::Analytics,
            source: '/tmp/video.mp4',
            model: 'yolo26s.pt',
            device: Device::Cpu,
            options: ['analytics_type' => 'line', 'json_file' => '/tmp/data.json', 'records' => 50],
        );

        Mockery::close();
    });
});
