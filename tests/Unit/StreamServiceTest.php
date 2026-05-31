<?php

declare(strict_types=1);

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Exceptions\FluentVisionException;
use B7s\FluentVision\Services\Providers\ProviderContract;
use B7s\FluentVision\Services\Providers\ProviderFactory;
use B7s\FluentVision\Services\PythonService;
use B7s\FluentVision\Services\StreamService;

describe('StreamService', function () {
    it('throws when provider does not support streaming', function () {
        $pythonService = Mockery::mock(PythonService::class);
        $provider = Mockery::mock(ProviderContract::class);
        $provider->shouldReceive('supportsStream')->andReturn(false);
        $provider->shouldReceive('name')->andReturn('nanodet');

        $providerFactory = Mockery::mock(ProviderFactory::class);
        $providerFactory->shouldReceive('make')->with(Provider::Nanodet)->andReturn($provider);

        $service = new StreamService($pythonService, $providerFactory);

        $service->stream(
            providerType: Provider::Nanodet,
            source: 'rtsp://test',
            model: 'model.pt',
            device: Device::Cpu,
            onFrame: static fn () => null,
        );
    })->throws(FluentVisionException::class);

    afterEach(fn () => Mockery::close());
});
