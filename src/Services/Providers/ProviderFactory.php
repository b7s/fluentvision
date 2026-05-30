<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services\Providers;

use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Exceptions\ProviderNotFoundException;

class ProviderFactory
{
    public function __construct(
        private readonly string $nanodetRepoPath = '',
    ) {}

    public function make(Provider $provider): ProviderContract
    {
        return match ($provider) {
            Provider::Ultralytics => new UltralyticsProvider,
            Provider::Nanodet => new NanodetProvider($this->nanodetRepoPath),
        };
    }

    public function makeFromString(string $name): ProviderContract
    {
        $provider = Provider::tryFrom($name);

        if ($provider === null) {
            throw ProviderNotFoundException::fromName($name);
        }

        return $this->make($provider);
    }
}
