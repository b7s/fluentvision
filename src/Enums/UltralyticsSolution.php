<?php

declare(strict_types=1);

namespace B7s\FluentVision\Enums;

enum UltralyticsSolution: string
{
    use HasEnumOptions;

    case Count = 'count';
    case Crop = 'crop';
    case Blur = 'blur';
    case Workout = 'workout';
    case Heatmap = 'heatmap';
    case ISegment = 'isegment';
    case VisionEye = 'visioneye';
    case Speed = 'speed';
    case Queue = 'queue';
    case Analytics = 'analytics';
    case TrackZone = 'trackzone';
    case Distance = 'distance';

    public function label(): string
    {
        return match ($this) {
            self::Count => 'Object Counting',
            self::Crop => 'Object Cropping',
            self::Blur => 'Object Blurring',
            self::Workout => 'Workouts Monitoring',
            self::Heatmap => 'Heatmaps',
            self::ISegment => 'Instance Segmentation',
            self::VisionEye => 'VisionEye Mapping',
            self::Speed => 'Speed Estimation',
            self::Queue => 'Queue Management',
            self::Analytics => 'Analytics',
            self::TrackZone => 'Track in Zone',
            self::Distance => 'Distance Calculation',
        };
    }

    public function pythonClass(): string
    {
        return match ($this) {
            self::Count => 'ObjectCounter',
            self::Crop => 'ObjectCropper',
            self::Blur => 'ObjectBlurrer',
            self::Workout => 'AIGym',
            self::Heatmap => 'Heatmap',
            self::ISegment => 'InstanceSegmentation',
            self::VisionEye => 'VisionEye',
            self::Speed => 'SpeedEstimator',
            self::Queue => 'QueueManager',
            self::Analytics => 'Analytics',
            self::TrackZone => 'TrackZone',
            self::Distance => 'DistanceCalculation',
        };
    }
}
