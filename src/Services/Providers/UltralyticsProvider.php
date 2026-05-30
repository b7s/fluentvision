<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services\Providers;

use B7s\FluentVision\Enums\Device;

class UltralyticsProvider implements ProviderContract
{
    public function name(): string
    {
        return 'ultralytics';
    }

    public function scriptPath(): string
    {
        return __DIR__.'/../../../scripts/ultralytics_inference.py';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function buildArguments(
        string $imagePath,
        string $model,
        Device $device,
        array $options = [],
    ): array {
        $args = [
            '--image', $imagePath,
            '--model', $model,
            '--device', $device->toUltralyticsArg(),
        ];

        OptionBuilder::appendStringOption($options, $args, 'task', '--task');
        OptionBuilder::appendFloatOption($options, $args, 'conf', '--conf');
        OptionBuilder::appendFloatOption($options, $args, 'iou', '--iou');
        OptionBuilder::appendIntOption($options, $args, 'imgsz', '--imgsz');
        OptionBuilder::appendIntOption($options, $args, 'max_det', '--max-det');
        OptionBuilder::appendClassesOption($options, $args);
        OptionBuilder::appendBoolOption($options, $args, 'augment', '--augment');
        OptionBuilder::appendBoolOption($options, $args, 'agnostic_nms', '--agnostic-nms');
        OptionBuilder::appendBoolOption($options, $args, 'half', '--half');
        OptionBuilder::appendBoolOption($options, $args, 'end2end', '--end2end');
        OptionBuilder::appendBoolOption($options, $args, 'save', '--save');
        OptionBuilder::appendIntOption($options, $args, 'vid_stride', '--vid-stride');

        return $args;
    }

    public function supportsVideo(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function buildVideoArguments(
        string $videoPath,
        string $model,
        Device $device,
        array $options = [],
    ): array {
        $args = [
            '--video', $videoPath,
            '--model', $model,
            '--device', $device->toUltralyticsArg(),
        ];

        OptionBuilder::appendVideoOptions($options, $args);

        return $args;
    }
}
