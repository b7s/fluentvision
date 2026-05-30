<?php

declare(strict_types=1);

/**
 * YOLOE-26 open-vocabulary detection example.
 *
 * YOLOE models accept text prompts to detect arbitrary concepts
 * beyond the standard 80 COCO classes — e.g. clothing color,
 * scene attributes, materials, or domain-specific objects.
 *
 * Direct usage (standalone): php examples/yoloe_detect.php
 * Package usage (installed): See https://github.com/b7s/fluentvision/docs/usage.md
 */

use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\FluentVision;

require __DIR__.'/../vendor/autoload.php';

$imagesDir = __DIR__.'/images';

echo "=== YOLOE-26 Open-Vocabulary Detection ===\n\n";

echo "--- Factory workers (prompted: person, hard hat, person wearing yellow) ---\n";

$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26s)
    ->useCpu()
    ->conf(0.4)
    ->prompts(['person', 'hard hat', 'person wearing yellow'])
    ->image($imagesDir.'/factory-workers-handshaking-each-other-production-line-yellow-helmet.jpg')
    ->detect();

echo "Model: {$result->model}\n";
echo "Time: {$result->inferenceTime}ms\n";
echo "Detections: {$result->getDetectionCount()}\n";

foreach ($result->detections as $i => $d) {
    echo sprintf(
        " [%d] %-25s %.1f%% box(%.0f, %.0f, %.0f, %.0f)\n",
        $i + 1,
        $d->class,
        $d->confidence * 100,
        $d->box->x1,
        $d->box->y1,
        $d->box->x2,
        $d->box->y2,
    );
}

echo "\n--- Woman with coffee (prompted: person, cup, nighttime scene, daytime scene) ---\n";

$result2 = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26s)
    ->useCpu()
    ->conf(0.4)
    ->prompts(['person', 'cup', 'nighttime scene', 'daytime scene'])
    ->image($imagesDir.'/woman-cup-coffe.jpg')
    ->detect();

echo "Model: {$result2->model}\n";
echo "Time: {$result2->inferenceTime}ms\n";
echo "Detections: {$result2->getDetectionCount()}\n";

foreach ($result2->detections as $i => $d) {
    echo sprintf(
        " [%d] %-25s %.1f%% box(%.0f, %.0f, %.0f, %.0f)\n",
        $i + 1,
        $d->class,
        $d->confidence * 100,
        $d->box->x1,
        $d->box->y1,
        $d->box->x2,
        $d->box->y2,
    );
}

echo "\n--- Modern workspace (no prompts — prompt-free variant) ---\n";

$result3 = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26sPF)
    ->useCpu()
    ->conf(0.4)
    ->image($imagesDir.'/modern-workspace-with-laptop-coffee-plants.jpg')
    ->detect();

echo "Model: {$result3->model}\n";
echo "Time: {$result3->inferenceTime}ms\n";
echo "Detections: {$result3->getDetectionCount()}\n";

foreach ($result3->detections as $i => $d) {
    echo sprintf(
        " [%d] %-25s %.1f%% box(%.0f, %.0f, %.0f, %.0f)\n",
        $i + 1,
        $d->class,
        $d->confidence * 100,
        $d->box->x1,
        $d->box->y1,
        $d->box->x2,
        $d->box->y2,
    );
}

echo "\nDone.\n";
