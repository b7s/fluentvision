<?php

declare(strict_types=1);

/**
 * NanoDet-Plus detection example.
 *
 * Direct usage (standalone):  php examples/nanodet_detect.php
 * Package usage (installed):  See https://github.com/b7s/fluentvision/docs/usage.md
 */

use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\FluentVision;

require __DIR__.'/../vendor/autoload.php';

$imagesDir = __DIR__.'/images';

$images = [
    'factory-workers-handshaking-each-other-production-line-yellow-helmet.jpg',
    'modern-workspace-with-laptop-coffee-plants.jpg',
    'woman-cup-coffe.jpg',
];

echo "=== NanoDet-Plus Detection ===\n\n";

foreach ($images as $filename) {
    $path = $imagesDir.'/'.$filename;

    echo "--- {$filename} ---\n";

    $result = FluentVision::make()
        ->useNanodet()
        ->model(NanodetModel::PlusM416)
        ->useCpu()
        ->conf(0.4)
        ->image($path)
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
