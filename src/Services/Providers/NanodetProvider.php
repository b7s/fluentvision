<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services\Providers;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;

class NanodetProvider implements ProviderContract
{
    public function __construct(
        private readonly string $nanodetRepoPath = '',
    ) {}

    public function name(): string
    {
        return 'nanodet';
    }

    public function scriptPath(): string
    {
        return __DIR__.'/../../../scripts/nanodet_inference.py';
    }

    public function supportsVideo(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function buildArguments(
        string $mediaPath,
        MediaType $mediaType,
        string $model,
        Device $device,
        array $options = [],
    ): array {
        $mediaFlag = $mediaType->isVideo() ? '--video' : '--image';

        $args = [
            $mediaFlag, $mediaPath,
            '--model', $model,
            '--device', $device->toNanodetArg(),
        ];

        $this->appendNanodetOptions($options, $args);
        OptionBuilder::appendFloatOption($options, $args, 'conf', '--conf');
        OptionBuilder::appendIntOption($options, $args, 'imgsz', '--imgsz');
        OptionBuilder::appendBoolOption($options, $args, 'save', '--save');
        OptionBuilder::appendStringOption($options, $args, 'save_path', '--save-path');

        if ($mediaType->isVideo()) {
            OptionBuilder::appendVideoOptions($options, $args);
        }

        return $args;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    private function appendNanodetOptions(array $options, array &$args): void
    {
        OptionBuilder::appendStringOption($options, $args, 'config', '--config');
        OptionBuilder::appendStringOption($options, $args, 'checkpoint', '--checkpoint');

        if ($this->nanodetRepoPath !== '') {
            $args[] = '--nanodet-path';
            $args[] = $this->nanodetRepoPath;
        }
    }
}
