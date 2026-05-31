<?php

declare(strict_types=1);

/**
 * Ultralytics YOLO26 detection example.
 *
 * Direct usage (standalone):  php examples/ultralytics_detect.php
 * Package usage (installed):  See https://github.com/b7s/fluentvision/docs/usage.md
 */

use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;

require __DIR__.'/../vendor/autoload.php';

$imagesDir = __DIR__.'/images';

$images = [
    'factory-workers-handshaking-each-other-production-line-yellow-helmet.jpg',
    'modern-workspace-with-laptop-coffee-plants.jpg',
    'woman-cup-coffe.jpg',
];

echo "=== Ultralytics YOLO26 Detection ===\n\n";

foreach ($images as $filename) {
    $path = $imagesDir.'/'.$filename;

    echo "--- {$filename} ---\n";

    $result = FluentVision::make()
        ->useUltralytics()
        ->model(YoloModel::YOLO26s)
        ->useCpu()
        ->conf(0.4)
        ->media($path)
        ->detect();

    echo "Model:       {$result->model}\n";
    echo "Provider:    {$result->provider}\n";
    echo "Time:        {$result->inferenceTime}ms\n";
    echo "Detections:  {$result->getDetectionCount()}\n";

    foreach ($result->detections as $i => $d) {
        echo sprintf(
            "  [%d] %-20s %.1f%%  box(%.0f, %.0f, %.0f, %.0f)\n",
            $i + 1,
            $d->class,
            $d->confidence * 100,
            $d->box->x1,
            $d->box->y1,
            $d->box->x2,
            $d->box->y2,
        );
    }

    echo "\n";
}
