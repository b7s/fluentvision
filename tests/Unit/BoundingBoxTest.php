<?php

declare(strict_types=1);

use B7s\FluentVision\Support\BoundingBox;

describe('BoundingBox', function () {
    it('creates from coordinates', function () {
        $box = new BoundingBox(x1: 10.0, y1: 20.0, x2: 110.0, y2: 120.0);

        expect($box->x1)->toBe(10.0);
        expect($box->y1)->toBe(20.0);
        expect($box->x2)->toBe(110.0);
        expect($box->y2)->toBe(120.0);
    });

    it('calculates width and height', function () {
        $box = new BoundingBox(x1: 10.0, y1: 20.0, x2: 110.0, y2: 120.0);

        expect($box->width())->toBe(100.0);
        expect($box->height())->toBe(100.0);
    });

    it('calculates area', function () {
        $box = new BoundingBox(x1: 0.0, y1: 0.0, x2: 200.0, y2: 150.0);

        expect($box->area())->toBe(30000.0);
    });

    it('calculates center point', function () {
        $box = new BoundingBox(x1: 0.0, y1: 0.0, x2: 100.0, y2: 200.0);

        expect($box->centerX())->toBe(50.0);
        expect($box->centerY())->toBe(100.0);
    });

    it('converts to array', function () {
        $box = new BoundingBox(x1: 10.0, y1: 20.0, x2: 110.0, y2: 120.0);
        $array = $box->toArray();

        expect($array)->toHaveKey('x1');
        expect($array)->toHaveKey('width');
        expect($array)->toHaveKey('area');
        expect($array['x1'])->toBe(10.0);
        expect($array['width'])->toBe(100.0);
    });

    it('creates from array', function () {
        $box = BoundingBox::fromArray([
            'x1' => 5.0,
            'y1' => 10.0,
            'x2' => 105.0,
            'y2' => 110.0,
        ]);

        expect($box->x1)->toBe(5.0);
        expect($box->width())->toBe(100.0);
    });

    it('handles missing array keys with defaults', function () {
        $box = BoundingBox::fromArray([]);

        expect($box->x1)->toBe(0.0);
        expect($box->y1)->toBe(0.0);
    });
});
