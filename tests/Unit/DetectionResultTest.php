<?php

declare(strict_types=1);

use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Support\BoundingBox;

describe('DetectionResult', function () {
    it('creates with all properties', function () {
        $box = new BoundingBox(x1: 10.0, y1: 20.0, x2: 110.0, y2: 120.0);
        $result = new DetectionResult(class: 'person', confidence: 0.95, box: $box);

        expect($result->class)->toBe('person');
        expect($result->confidence)->toBe(0.95);
        expect($result->box->x1)->toBe(10.0);
    });

    it('converts to array', function () {
        $box = new BoundingBox(x1: 10.0, y1: 20.0, x2: 110.0, y2: 120.0);
        $result = new DetectionResult(class: 'car', confidence: 0.88, box: $box);
        $array = $result->toArray();

        expect($array['class'])->toBe('car');
        expect($array['confidence'])->toBe(0.88);
        expect($array['box'])->toHaveKey('x1');
    });

    it('creates from array', function () {
        $result = DetectionResult::fromArray([
            'class' => 'dog',
            'confidence' => 0.72,
            'box' => ['x1' => 5, 'y1' => 10, 'x2' => 50, 'y2' => 60],
        ]);

        expect($result->class)->toBe('dog');
        expect($result->confidence)->toBe(0.72);
        expect($result->box->x1)->toBe(5.0);
    });

    it('handles missing array keys with defaults', function () {
        $result = DetectionResult::fromArray([]);

        expect($result->class)->toBe('');
        expect($result->confidence)->toBe(0.0);
        expect($result->box->x1)->toBe(0.0);
    });
});
