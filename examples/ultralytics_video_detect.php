<?php

declare(strict_types=1);

/**
 * Ultralytics YOLO26 video detection and annotation example.
 *
 * Direct usage (standalone): php examples/ultralytics_video_detect.php
 */

use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;

require __DIR__.'/../vendor/autoload.php';

$videoPath = __DIR__.'/videos/people-crossing-cars.mp4';

echo "=== Ultralytics YOLO26 Video Detection ===\n\n";

$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->useCpu()
    ->conf(0.4)
    ->everyNframes(10)
    ->media($videoPath)
    ->detect();

echo "Video:   {$result->videoPath}\n";
echo "Model:   {$result->model}\n";
echo "Provider: {$result->provider}\n";
echo "Total time: {$result->totalInferenceTime}ms\n";
echo "Frames:  {$result->getFrameCount()}\n";
echo "Detections: {$result->getTotalDetections()}\n";
echo "Avg time: {$result->getAverageInferenceTime()}ms\n\n";

foreach ($result->frames as $i => $frame) {
    $count = count($frame->detections);
    echo sprintf("  Frame %3d: %2d detections", $i + 1, $count);

    if ($count > 0) {
        $classes = [];
        foreach ($frame->detections as $d) {
            $classes[] = $d->class;
        }
        echo ' (' . implode(', ', array_unique($classes)) . ')';
    }

    echo "\n";
}

echo "\n=== Video process() — Detect + Annotate in one call ===\n\n";

$processResult = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->useCpu()
    ->conf(0.4)
    ->everyNframes(10)
    ->savePath(__DIR__.'/output/video-process')
    ->withDetections()
    ->withAnnotation()
    ->media($videoPath)
    ->process();

echo "Detections: {$processResult->getDetectionCount()}\n";
echo "Total time: {$processResult->getTotalTime()}ms\n";
echo "Annotated: {$processResult->getAnnotatedPath()}\n";
echo "Has annotated file: " . ($processResult->hasAnnotatedImage() ? 'yes' : 'no') . "\n";
