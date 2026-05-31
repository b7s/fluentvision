<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Exceptions\ProviderNotFoundException;
use B7s\FluentVision\Services\Providers\NanodetProvider;
use B7s\FluentVision\Services\Providers\ProviderFactory;
use B7s\FluentVision\Services\Providers\UltralyticsProvider;

describe('ProviderFactory', function () {
    it('creates ultralytics provider', function () {
        $factory = new ProviderFactory;
        $provider = $factory->make(Provider::Ultralytics);

        expect($provider)->toBeInstanceOf(UltralyticsProvider::class);
        expect($provider->name())->toBe('ultralytics');
    });

    it('creates nanodet provider', function () {
        $factory = new ProviderFactory;
        $provider = $factory->make(Provider::Nanodet);

        expect($provider)->toBeInstanceOf(NanodetProvider::class);
        expect($provider->name())->toBe('nanodet');
    });

    it('creates provider from string', function () {
        $factory = new ProviderFactory;

        expect($factory->makeFromString('ultralytics'))->toBeInstanceOf(UltralyticsProvider::class);
        expect($factory->makeFromString('nanodet'))->toBeInstanceOf(NanodetProvider::class);
    });

    it('throws for invalid provider string', function () {
        $factory = new ProviderFactory;

        expect(fn () => $factory->makeFromString('invalid'))->toThrow(ProviderNotFoundException::class);
    });

    it('passes nanodet repo path to nanodet provider', function () {
        $factory = new ProviderFactory(nanodetRepoPath: '/custom/nanodet');
        $provider = $factory->make(Provider::Nanodet);

        expect($provider)->toBeInstanceOf(NanodetProvider::class);
    });
});

describe('UltralyticsProvider', function () {
    it('builds image arguments', function () {
        $provider = new UltralyticsProvider;
        $args = $provider->buildArguments(
            mediaPath: '/tmp/test.jpg',
            mediaType: MediaType::Image,
            model: 'yolo26s.pt',
            device: Device::Cpu,
            options: ['conf' => 0.5, 'iou' => 0.45],
        );

        expect($args)->toContain('--image')
            ->and($args)->toContain('/tmp/test.jpg')
            ->and($args)->toContain('--model')
            ->and($args)->toContain('yolo26s.pt')
            ->and($args)->toContain('--conf')
            ->and($args)->toContain('0.5');
    });

    it('builds video arguments', function () {
        $provider = new UltralyticsProvider;

        expect($provider->supportsVideo())->toBeTrue();

        $args = $provider->buildArguments(
            mediaPath: '/tmp/test.mp4',
            mediaType: MediaType::Video,
            model: 'yolo26s.pt',
            device: Device::Gpu,
        );

        expect($args)->toContain('--video')
            ->and($args)->toContain('/tmp/test.mp4');
    });
});

describe('NanodetProvider', function () {
    it('builds image arguments', function () {
        $provider = new NanodetProvider(nanodetRepoPath: '/opt/nanodet');
        $args = $provider->buildArguments(
            mediaPath: '/tmp/test.jpg',
            mediaType: MediaType::Image,
            model: 'nanodet-plus-m-416',
            device: Device::Cpu,
            options: ['config' => '/tmp/config.yml', 'checkpoint' => '/tmp/model.ckpt'],
        );

        expect($args)->toContain('--image')
            ->and($args)->toContain('--config')
            ->and($args)->toContain('/tmp/config.yml')
            ->and($args)->toContain('--nanodet-path')
            ->and($args)->toContain('/opt/nanodet');
    });

    it('builds video arguments', function () {
        $provider = new NanodetProvider;

        expect($provider->supportsVideo())->toBeTrue();

        $args = $provider->buildArguments(
            mediaPath: '/tmp/test.mp4',
            mediaType: MediaType::Video,
            model: 'nanodet-plus-m-416',
            device: Device::Gpu,
        );

        expect($args)->toContain('--video')
            ->and($args)->toContain('cuda:0');
    });
});
