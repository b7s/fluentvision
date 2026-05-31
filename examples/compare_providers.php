<?php

declare(strict_types=1);

/**
 * Compare Ultralytics vs NanoDet detection example.
 *
 * Direct usage (standalone):  php examples/compare_providers.php
 * Package usage (installed):  See https://github.com/b7s/fluentvision/docs/usage.md
 */

use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;

require __DIR__.'/../vendor/autoload.php';

$imagesDir = __DIR__.'/images';

$images = [
    'factory-workers-handshaking-each-other-production-line-yellow-helmet.jpg',
    'modern-workspace-with-laptop-coffee-plants.jpg',
    'woman-cup-coffe.jpg',
];

$providers = [
    'Ultralytics YOLO26s' => fn (string $path) => FluentVision::make()
        ->useUltralytics()
        ->model(YoloModel::YOLO26s)
        ->useCpu()
        ->confidence(0.4)
        ->media($path)
        ->detect(),
    'NanoDet-Plus M 416' => fn (string $path) => FluentVision::make()
        ->useNanodet()
        ->model(NanodetModel::PlusM416)
        ->useCpu()
        ->confidence(0.4)
        ->media($path)
        ->detect(),
];

echo "=== Provider Comparison ===\n\n";

foreach ($images as $filename) {
    $path = $imagesDir.'/'.$filename;

    echo "--- {$filename} ---\n";

    foreach ($providers as $label => $detect) {
        $result = $detect($path);

        $classes = array_map(
            static fn ($d): string => $d->class,
            $result->detections,
        );

        $uniqueClasses = array_unique($classes);

        echo sprintf(
            "  %-25s | %2d detections | %.1fms | %s\n",
            $label,
            $result->getDetectionCount(),
            $result->inferenceTime,
            implode(', ', $uniqueClasses) ?: '(none)',
        );
    }

    echo "\n";
}
