<?php

declare(strict_types=1);

/**
 * Ultralytics annotation (save annotated image) example.
 *
 * Direct usage (standalone):  php examples/annotate_example.php
 * Package usage (installed):  See https://github.com/b7s/fluentvision/docs/usage.md
 */

use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;

require __DIR__.'/../vendor/autoload.php';

$imagesDir = __DIR__.'/images';

$images = [
    'factory-workers-handshaking-each-other-production-line-yellow-helmet.jpg',
    'modern-workspace-with-laptop-coffee-plants.jpg',
    'woman-bike-cars-trees-road-day.jpg',
    'woman-cup-coffe.jpg',
];

$outputDir = __DIR__.'/output';

if (! is_dir($outputDir) && ! mkdir($outputDir, 0755, true) && ! is_dir($outputDir)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
}

echo "=== Ultralytics Annotated Output ===\n\n";

foreach ($images as $filename) {
    $path = $imagesDir.'/'.$filename;

    echo "--- {$filename} ---\n";

    $result = FluentVision::make()
        ->useUltralytics()
        ->model(YoloModel::YOLO26m)
        ->useCpu()
        ->conf(0.4)
        ->image($path)
        ->annotate();

    if ($result->hasAnnotatedImage()) {
        echo "Annotated:   {$result->annotatedPath}\n";
        echo "Detections:  {$result->detectionCount}\n";
        echo "Model:       {$result->model}\n";
        echo "Provider:    {$result->provider}\n";
    } else {
        echo "Failed to generate annotated image.\n";
    }

    echo "\n";
}
