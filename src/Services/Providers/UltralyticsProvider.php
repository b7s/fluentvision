<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services\Providers;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;

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
            '--device', $device->toUltralyticsArg(),
        ];

        OptionBuilder::appendStringOption($options, $args, 'task', '--task');
        OptionBuilder::appendFloatOption($options, $args, 'conf', '--conf');
        OptionBuilder::appendFloatOption($options, $args, 'iou', '--iou');
        OptionBuilder::appendIntOption($options, $args, 'imgsz', '--imgsz');
        OptionBuilder::appendIntOption($options, $args, 'max_det', '--max-det');
        OptionBuilder::appendClassesOption($options, $args);
        OptionBuilder::appendPromptsOption($options, $args);
        OptionBuilder::appendBoolOption($options, $args, 'augment', '--augment');
        OptionBuilder::appendBoolOption($options, $args, 'agnostic_nms', '--agnostic-nms');
        OptionBuilder::appendBoolOption($options, $args, 'half', '--half');
        OptionBuilder::appendBoolOption($options, $args, 'end2end', '--end2end');
        OptionBuilder::appendBoolOption($options, $args, 'save', '--save');
        OptionBuilder::appendStringOption($options, $args, 'save_path', '--save-path');
        OptionBuilder::appendIntOption($options, $args, 'vid_stride', '--vid-stride');

        return $args;
    }
}
