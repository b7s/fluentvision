<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\UltralyticsSolution;

describe('UltralyticsSolution', function () {
    it('has all 12 solution cases', function () {
        $cases = UltralyticsSolution::cases();

        expect($cases)->toHaveCount(12);
    });

    it('returns correct labels', function () {
        expect(UltralyticsSolution::Count->label())->toBe('Object Counting')
            ->and(UltralyticsSolution::Heatmap->label())->toBe('Heatmaps')
            ->and(UltralyticsSolution::Speed->label())->toBe('Speed Estimation')
            ->and(UltralyticsSolution::Workout->label())->toBe('Workouts Monitoring')
            ->and(UltralyticsSolution::Queue->label())->toBe('Queue Management')
            ->and(UltralyticsSolution::Blur->label())->toBe('Object Blurring')
            ->and(UltralyticsSolution::Crop->label())->toBe('Object Cropping')
            ->and(UltralyticsSolution::VisionEye->label())->toBe('VisionEye Mapping')
            ->and(UltralyticsSolution::ISegment->label())->toBe('Instance Segmentation')
            ->and(UltralyticsSolution::Analytics->label())->toBe('Analytics')
            ->and(UltralyticsSolution::TrackZone->label())->toBe('Track in Zone')
            ->and(UltralyticsSolution::Distance->label())->toBe('Distance Calculation');
    });

    it('returns correct python class names', function () {
        expect(UltralyticsSolution::Count->pythonClass())->toBe('ObjectCounter')
            ->and(UltralyticsSolution::Heatmap->pythonClass())->toBe('Heatmap')
            ->and(UltralyticsSolution::Speed->pythonClass())->toBe('SpeedEstimator')
            ->and(UltralyticsSolution::Workout->pythonClass())->toBe('AIGym')
            ->and(UltralyticsSolution::Queue->pythonClass())->toBe('QueueManager')
            ->and(UltralyticsSolution::Blur->pythonClass())->toBe('ObjectBlurrer')
            ->and(UltralyticsSolution::Crop->pythonClass())->toBe('ObjectCropper')
            ->and(UltralyticsSolution::VisionEye->pythonClass())->toBe('VisionEye')
            ->and(UltralyticsSolution::ISegment->pythonClass())->toBe('InstanceSegmentation')
            ->and(UltralyticsSolution::Analytics->pythonClass())->toBe('Analytics')
            ->and(UltralyticsSolution::TrackZone->pythonClass())->toBe('TrackZone')
            ->and(UltralyticsSolution::Distance->pythonClass())->toBe('DistanceCalculation');
    });

    it('provides options array for dropdowns', function () {
        $options = UltralyticsSolution::options();

        expect($options)->toHaveCount(12)
            ->and($options)->toHaveKey('count')
            ->and($options['count'])->toBe('Object Counting');
    });

    it('provides values array', function () {
        $values = UltralyticsSolution::values();

        expect($values)->toHaveCount(12)
            ->and($values)->toContain('count')
            ->and($values)->toContain('heatmap')
            ->and($values)->toContain('speed');
    });
});
